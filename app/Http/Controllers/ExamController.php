<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentSubmission;
use App\Models\SubmissionDetail;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    /**
     * Start exam: create submission record, return questions (no IsCorrect).
     */
    public function start(Request $request, $testId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = Student::where('UserId', $user->id)->first();
        if (! $student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $test = Test::with(['questions.answers'])->find($testId);
        if (! $test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        // Security check: only Published tests and only if ScheduledAt has passed (with 15sec grace period)
        if ($test->Status !== 'Published') {
            return response()->json(['message' => 'This test is not yet published.'], 403);
        }
        if ($test->ScheduledAt && $test->ScheduledAt->isFuture() && $test->ScheduledAt->diffInSeconds(now()) > 15) {
            return response()->json(['message' => 'This test is scheduled for ' . $test->ScheduledAt->toDateTimeString()], 403);
        }

        // Check if exam duration has ended globally
        $endAt = null;
        if ($test->FinishedAt) {
            $endAt = $test->FinishedAt;
        } elseif ($test->ScheduledAt) {
            $endAt = $test->ScheduledAt->copy()->addMinutes($test->DurationMinutes);
        }

        if ($endAt && now()->greaterThan($endAt)) {
            return response()->json(['message' => 'This exam has already finished and is no longer available.'], 403);
        }

        // Check for an existing incomplete submission
        $existing = StudentSubmission::where('StudentId', $student->StudentId)
            ->where('TestId', $testId)
            ->whereNull('CompletedAt')
            ->first();

        if ($existing) {
            $submission = $existing;
        } else {
            $submission = StudentSubmission::create([
                'StudentId' => $student->StudentId,
                'TestId'    => $testId,
                'StartedAt' => now(),
            ]);
        }

        // Build questions payload WITHOUT IsCorrect
        $questions = $test->questions->map(function ($question) {
            return [
                'id'      => $question->QuestionId,
                'text'    => $question->QuestionText,
                'answers' => $question->answers->map(fn($a) => [
                    'id'   => $a->AnswerId,
                    'text' => $a->AnswerText,
                ])->values(),
            ];
        })->values();

        return response()->json([
            'submissionId'    => $submission->SubmissionId,
            'testId'          => $test->TestId,
            'testName'        => $test->TestName,
            'durationMinutes' => $test->DurationMinutes,
            'totalMarks'      => $test->TotalMarks,
            'scheduledAt'     => $test->ScheduledAt,
            'finishedAt'      => $test->FinishedAt,
            'questions'       => $questions,
        ]);
    }

    /**
     * Save a single answer during the exam.
     */
    public function saveAnswer(Request $request)
    {
        $data = $request->validate([
            'submissionId'     => ['required', 'integer'],
            'questionId'       => ['required', 'integer'],
            'selectedAnswerId' => ['nullable', 'integer'],
        ]);

        $answer = null;
        $isCorrect = false;

        if ($data['selectedAnswerId']) {
            $answer = Answer::find($data['selectedAnswerId']);
            $isCorrect = $answer ? (bool) $answer->IsCorrect : false;
        }

        SubmissionDetail::updateOrCreate(
            [
                'SubmissionId' => $data['submissionId'],
                'QuestionId'   => $data['questionId'],
            ],
            [
                'SelectedAnswerId' => $data['selectedAnswerId'],
                'IsCorrect'        => $isCorrect,
            ]
        );

        return response()->json(['message' => 'Answer saved.']);
    }

    /**
     * Complete exam: calculate score and mark finished.
     */
    public function complete(Request $request, $submissionId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = Student::where('UserId', $user->id)->first();
        if (! $student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $submission = StudentSubmission::where('SubmissionId', $submissionId)
            ->where('StudentId', $student->StudentId)
            ->first();

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        if ($submission->CompletedAt) {
            return response()->json(['message' => 'Already completed.', 'submissionId' => $submission->SubmissionId]);
        }

        $test = Test::find($submission->TestId);
        $totalQuestions = Question::where('TestId', $submission->TestId)->count();
        $totalCorrect   = SubmissionDetail::where('SubmissionId', $submissionId)->where('IsCorrect', true)->count();

        $score = $totalQuestions > 0
            ? round(($totalCorrect / $totalQuestions) * $test->TotalMarks, 2)
            : 0;

        $submission->TotalCorrect  = $totalCorrect;
        $submission->Score         = $score;
        $submission->CompletedAt   = now();
        $submission->save();

        return response()->json([
            'message'        => 'Exam completed.',
            'submissionId'   => $submission->SubmissionId,
            'totalCorrect'   => $totalCorrect,
            'totalQuestions' => $totalQuestions,
            'totalMarks'     => $test->TotalMarks,
            'score'          => $score,
        ]);
    }
}
