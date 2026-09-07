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

        $student = ($user instanceof Student) 
            ? $user 
            : (Student::find($user->StudentId ?? $user->id) ?? Student::where('UserId', $user->id)->first());
        if (! $student) {
            return response()->json(['results' => []]);
        }

        $submissions = DB::table('tblstudentsubmission as ss')
            ->join('tbltest as t', 'ss.TestId', '=', 't.TestId')
            ->where('ss.StudentId', $student->StudentId)
            ->whereNotNull('ss.CompletedAt')
            ->select(
                'ss.SubmissionId as id',
                'ss.SubmissionId as submissionId',
                't.TestId as testId',
                't.TestName as testName',
                't.TotalMarks as totalMarks',
                't.DurationMinutes as durationMinutes',
                'ss.TotalCorrect as totalCorrect',
                'ss.Score as score',
                'ss.StartedAt as startedAt',
                'ss.CompletedAt as completedAt',
                DB::raw("'Submitted' as status")
            )
            ->orderBy('ss.CompletedAt', 'desc')
            ->get();

        return response()->json(['results' => $submissions]);
    }

    /**
     * Detailed result for one submission.
     * Accessible by Admins and the Student who took the exam.
     */
    public function submissionDetail(Request $request, $submissionId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $submission = StudentSubmission::with([
            'student',
            'test.questions.answers',
            'details.question',
            'details.selectedAnswer'
        ])->find($submissionId);

        if (! $submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        // Determine user type and enforce permissions:
        // Students can only view their own submission; Admins/Super Admins can view any submission.
        $isStudent = ($user instanceof Student) || (($user->role ?? null) === 'Student');
        if ($isStudent) {
            $student = ($user instanceof Student) 
                ? $user 
                : (Student::find($user->StudentId ?? $user->id) ?? Student::where('UserId', $user->id)->first());

            if (! $student || (int)$submission->StudentId !== (int)$student->StudentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'លទ្ធផលប្រឡងនេះមិនមែនជារបស់អ្នកទេ (Forbidden. You do not have permission to view this submission).'
                ], 403);
            }
        }

        $test = $submission->test;
        $allQuestions = $test ? $test->questions : collect();
        $totalQuestions = $allQuestions->count();
        $correct = $submission->TotalCorrect ?? 0;
        
        // Use the loaded details collection to avoid redundant DB queries
        $details = $submission->details;

        // Elapsed time in minutes
        $startedAt    = $submission->StartedAt ? \Carbon\Carbon::parse($submission->StartedAt) : null;
        $completedAt  = $submission->CompletedAt ? \Carbon\Carbon::parse($submission->CompletedAt) : null;
        $elapsedMin   = ($startedAt && $completedAt)
            ? (int) max(0, round(($completedAt->getTimestamp() - $startedAt->getTimestamp()) / 60))
            : 0;

        $accuracy = $totalQuestions > 0
            ? round(($correct / $totalQuestions) * 100, 1)
            : 0;

        $questionsData = $allQuestions->map(function ($q) use ($details) {
            $detail = $details->firstWhere('QuestionId', $q->QuestionId);

            $answers = $q->answers->map(fn($a) => [
                'id'        => $a->AnswerId,
                'text'      => $a->AnswerText,
                'isCorrect' => (bool) $a->IsCorrect,
            ]);

            $isSkipped = $detail === null || is_null($detail->SelectedAnswerId);

            return [
                'id'             => $q->QuestionId,
                'text'           => $q->QuestionText,
                'passage'        => $q->Passage,
                'points'         => $q->Points,
                'answers'        => $answers,
                'selectedId'     => $detail?->SelectedAnswerId,
                'selectedText'   => $detail?->selectedAnswer?->AnswerText,
                'isCorrect'      => (bool) ($detail?->IsCorrect ?? false),
                'skipped'        => $isSkipped,
            ];
        });

        $skipped = $questionsData->where('skipped', true)->count();
        $incorrect = max(0, $totalQuestions - $correct - $skipped);

        return response()->json([
            'submissionId'   => $submission->SubmissionId,
            'studentId'      => $submission->student ? ($submission->student->StudentCode ?? (string)$submission->student->StudentId) : 'N/A',
            'studentName'    => $submission->student ? trim(($submission->student->FirstName ?? '') . ' ' . ($submission->student->LastName ?? '')) : 'N/A',
            'testName'       => $test?->TestName,
            'totalMarks'     => $test?->TotalMarks,
            'score'          => $submission->Score,
            'totalCorrect'   => $correct,
            'totalQuestions' => $totalQuestions,
            'incorrect'      => $incorrect,
            'skipped'        => $skipped,
            'interruptions'  => (int) ($submission->Interruptions ?? 0),
            'accuracy'       => $accuracy,
            'elapsedMinutes' => $elapsedMin,
            'completedAt'    => $completedAt ? $completedAt->toIso8601String() : null,
            'questions'      => $questionsData,
        ]);
    }
}
