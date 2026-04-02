<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Student;
use App\Models\StudentSubmission;
use App\Models\SubmissionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    /**
     * Student's own submission history.
     */
    public function studentResults(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = Student::where('UserId', $user->id)->first();
        if (! $student) {
            return response()->json(['results' => []]);
        }

        $submissions = DB::table('tblStudentSubmission as ss')
            ->join('tblTest as t', 'ss.TestId', '=', 't.TestId')
            ->where('ss.StudentId', $student->StudentId)
            ->whereNotNull('ss.CompletedAt')
            ->select(
                'ss.SubmissionId as id',
                't.TestName as testName',
                't.TotalMarks as totalMarks',
                't.DurationMinutes as durationMinutes',
                'ss.TotalCorrect as totalCorrect',
                'ss.Score as score',
                'ss.StartedAt as startedAt',
                'ss.CompletedAt as completedAt'
            )
            ->orderBy('ss.CompletedAt', 'desc')
            ->get();

        return response()->json(['results' => $submissions]);
    }

    /**
     * Detailed result for one submission (per-question breakdown).
     */
    public function submissionDetail(Request $request, $submissionId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = Student::where('UserId', $user->id)->first();

        $submission = StudentSubmission::with(['student', 'test', 'details.question', 'details.selectedAnswer'])
            ->find($submissionId);

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        // Students can only see their own; admins see all
        if ($user->role === 'Student' && $student && $submission->StudentId !== $student->StudentId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $test = $submission->test;
        $totalQuestions = Question::where('TestId', $submission->TestId)->count();
        $correct        = $submission->TotalCorrect ?? 0;
        
        // Count how many questions were actually answered (non-null)
        $answered = $submission->details()
            ->whereNotNull('SelectedAnswerId')
            ->count();

        $skipped   = max(0, $totalQuestions - $answered);
        $incorrect = max(0, $answered - $correct);

        // Elapsed time in minutes
        $startedAt    = $submission->StartedAt;
        $completedAt  = $submission->CompletedAt;
        $elapsedMin   = $startedAt && $completedAt
            ? (int) round($startedAt->diffInSeconds($completedAt) / 60)
            : 0;

        $accuracy = $totalQuestions > 0
            ? round(($correct / $totalQuestions) * 100, 1)
            : 0;

        $questions = DB::table('tblQuestion as q')
            ->where('q.TestId', $submission->TestId)
            ->get()
            ->map(function ($q) use ($submissionId) {
                $detail = DB::table('tblSubmissionDetail')
                    ->where('SubmissionId', $submissionId)
                    ->where('QuestionId', $q->QuestionId)
                    ->first();

                $answers = DB::table('tblAnswer')
                    ->where('QuestionId', $q->QuestionId)
                    ->get()
                    ->map(fn($a) => [
                        'id'        => $a->AnswerId,
                        'text'      => $a->AnswerText,
                        'isCorrect' => (bool) $a->IsCorrect,
                    ]);

                $selectedAnswer = $detail?->SelectedAnswerId
                    ? DB::table('tblAnswer')->where('AnswerId', $detail->SelectedAnswerId)->first()
                    : null;

                return [
                    'id'             => $q->QuestionId,
                    'text'           => $q->QuestionText,
                    'answers'        => $answers,
                    'selectedId'     => $detail?->SelectedAnswerId,
                    'selectedText'   => $selectedAnswer?->AnswerText,
                    'isCorrect'      => (bool) ($detail?->IsCorrect ?? false),
                    'skipped'        => $detail === null,
                ];
            });

        return response()->json([
            'submissionId'   => $submission->SubmissionId,
            'studentName'    => $submission->student ? ($submission->student->FirstName . ' ' . $submission->student->LastName) : 'N/A',
            'testName'       => $test?->TestName,
            'totalMarks'     => $test?->TotalMarks,
            'score'          => $submission->Score,
            'totalCorrect'   => $correct,
            'totalQuestions' => $totalQuestions,
            'incorrect'      => $incorrect,
            'skipped'        => $skipped,
            'accuracy'       => $accuracy,
            'elapsedMinutes' => $elapsedMin,
            'completedAt'    => $completedAt ? $completedAt->toIso8601String() : null,
            'questions'      => $questions,
        ]);
    }
}
