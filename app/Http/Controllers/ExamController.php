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
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = ($user instanceof Student) 
            ? $user 
            : (Student::find($user->StudentId ?? $user->id) ?? Student::where('UserId', $user->id)->first());
        if (!$student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $test = Test::with(['questions.answers'])->find($testId);
        if (!$test) {
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

        // Security check: Only 1 attempt allowed per exam
        $alreadyCompleted = StudentSubmission::where('StudentId', $student->StudentId)
            ->where('TestId', $testId)
            ->whereNotNull('CompletedAt')
            ->first();

        if ($alreadyCompleted) {
            return response()->json([
                'message' => 'ការប្រឡងនេះអនុញ្ញាតឱ្យធ្វើតែ ១ លើកប៉ុណ្ណោះ។ អ្នកបានប្រឡងរួចរាល់ហើយ មិនអាចប្រឡងឡើងវិញបានទេ (You have already completed this exam).',
                'alreadyCompleted' => true,
                'submissionId' => $alreadyCompleted->SubmissionId
            ], 403);
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
                'TestId' => $testId,
                'StartedAt' => now(),
            ]);
        }

        // Build questions payload WITHOUT IsCorrect (except defaultAnswerId for example questions)
        $questions = $test->questions->map(function ($question) use ($submission) {
            $isExample = (bool)($question->IsExample || preg_match('/^(?:0\.|០\.|Example|Ex\.|គំរូ)/iu', trim($question->QuestionText)));
            $defaultAnswerId = null;
            if ($isExample) {
                $correct = $question->answers->firstWhere('IsCorrect', true) ?? $question->answers->first();
                $defaultAnswerId = $correct?->AnswerId;

                // Pre-save answer in SubmissionDetail if not already saved
                if ($defaultAnswerId && $submission) {
                    SubmissionDetail::updateOrCreate(
                        [
                            'SubmissionId' => $submission->SubmissionId,
                            'QuestionId' => $question->QuestionId,
                        ],
                        [
                            'SelectedAnswerId' => $defaultAnswerId,
                            'IsCorrect' => true,
                        ]
                    );
                }
            }

            return [
                'id' => $question->QuestionId,
                'text' => $question->QuestionText,
                'passage' => $question->Passage,
                'isExample' => $isExample,
                'defaultAnswerId' => $defaultAnswerId,
                'answers' => $question->answers->map(fn($a) => [
                    'id' => $a->AnswerId,
                    'text' => $a->AnswerText,
                ])->values(),
            ];
        })->values();

        // Load any previously saved answers for this incomplete submission
        $savedAnswers = [];
        $savedDetails = SubmissionDetail::where('SubmissionId', $submission->SubmissionId)->get();
        foreach ($savedDetails as $sd) {
            if ($sd->SelectedAnswerId) {
                $savedAnswers[$sd->QuestionId] = $sd->SelectedAnswerId;
            }
        }

        // Calculate remaining seconds strictly capped to DurationMinutes * 60
        $durationMin = (int) max(1, round((float) $test->DurationMinutes));
        $totalSeconds = $durationMin * 60;
        $elapsedSeconds = 0;
        if ($submission->StartedAt) {
            $startedAt = \Carbon\Carbon::parse($submission->StartedAt);
            $elapsedSeconds = max(0, now()->getTimestamp() - $startedAt->getTimestamp());
        }
        $remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);
        $remainingSeconds = min($remainingSeconds, $totalSeconds);

        // If test has a hard deadline FinishedAt, clamp further
        if ($test->FinishedAt) {
            $globalEnd = \Carbon\Carbon::parse($test->FinishedAt);
            $secondsUntilGlobalEnd = max(0, $globalEnd->getTimestamp() - now()->getTimestamp());
            $remainingSeconds = min($remainingSeconds, $secondsUntilGlobalEnd);
        }

        $sysSettings = AdminController::getSystemSettings();

        return response()->json([
            'submissionId' => $submission->SubmissionId,
            'testId' => $test->TestId,
            'testName' => $test->TestName,
            'durationMinutes' => $durationMin,
            'totalMarks' => $test->TotalMarks,
            'scheduledAt' => $test->ScheduledAt,
            'finishedAt' => $test->FinishedAt,
            'isStarted' => (bool) $existing,
            'startedAt' => $submission->StartedAt ? \Carbon\Carbon::parse($submission->StartedAt)->toIso8601String() : null,
            'serverTime' => now()->toIso8601String(),
            'questions' => $questions,
            'savedAnswers' => $savedAnswers,
            'interruptions' => (int) ($submission->Interruptions ?? 0),
            'remainingSeconds' => (int) $remainingSeconds,
            'antiCheatPause' => (bool) ($sysSettings['antiCheatPause'] ?? true),
            'autosaveIntervalSeconds' => (int) ($sysSettings['autosaveIntervalSeconds'] ?? 3),
            'autoSubmitOnTimeout' => (bool) ($sysSettings['autoSubmitOnTimeout'] ?? true),
            'studentTelegramLinked' => !empty($student->TelegramChatId),
            'telegramConnectUrl' => 'https://t.me/onlinexam_bot?start=link_' . urlencode($student->StudentCode ?: $student->StudentId),
        ]);
    }

    /**
     * Check submission status (used for real-time heartbeat sync / force submit detection).
     */
    public function checkStatus(Request $request, $submissionId)
    {
        $submission = StudentSubmission::find($submissionId);
        if (!$submission) {
            return response()->json(['exists' => false], 404);
        }

        return response()->json([
            'submissionId' => $submission->SubmissionId,
            'isCompleted' => !empty($submission->CompletedAt),
            'completedAt' => $submission->CompletedAt,
        ]);
    }

    /**
     * Record focus loss / tab switch incident in real time.
     */
    public function recordInterruption(Request $request)
    {
        $data = $request->validate([
            'submissionId' => ['required', 'integer'],
            'interruptions' => ['nullable', 'integer'],
        ]);

        $submission = StudentSubmission::find($data['submissionId']);
        if (!$submission || $submission->CompletedAt) {
            return response()->json(['message' => 'Submission not found or already completed.'], 404);
        }

        if (isset($data['interruptions']) && $data['interruptions'] !== null) {
            $submission->Interruptions = (int) $data['interruptions'];
        } else {
            $submission->increment('Interruptions');
        }
        $submission->save();

        if ((int)$submission->Interruptions === 3) {
            try {
                app(\App\Services\TelegramService::class)->sendInterruptionAlert($submission, (int)$submission->Interruptions);
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'success' => true,
            'interruptions' => (int) $submission->Interruptions,
        ]);
    }

    /**
     * Save a single answer during the exam.
     */
    public function saveAnswer(Request $request)
    {
        $data = $request->validate([
            'submissionId' => ['required', 'integer'],
            'questionId' => ['required', 'integer'],
            'selectedAnswerId' => ['nullable', 'integer'],
        ]);

        $submission = StudentSubmission::find($data['submissionId']);
        if ($submission && $submission->CompletedAt) {
            return response()->json([
                'message' => 'Submission already completed.',
                'isCompleted' => true
            ], 403);
        }

        $answer = null;
        $isCorrect = false;

        if ($data['selectedAnswerId']) {
            $answer = Answer::find($data['selectedAnswerId']);
            $isCorrect = $answer ? (bool) $answer->IsCorrect : false;
        }

        SubmissionDetail::updateOrCreate(
            [
                'SubmissionId' => $data['submissionId'],
                'QuestionId' => $data['questionId'],
            ],
            [
                'SelectedAnswerId' => $data['selectedAnswerId'],
                'IsCorrect' => $isCorrect,
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
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = ($user instanceof Student) 
            ? $user 
            : (Student::find($user->StudentId ?? $user->id) ?? Student::where('UserId', $user->id)->first());
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $submission = StudentSubmission::where('SubmissionId', $submissionId)
            ->where('StudentId', $student->StudentId)
            ->first();

        if (!$submission) {
            return response()->json(['message' => 'Submission not found.'], 404);
        }

        if ($submission->CompletedAt) {
            return response()->json(['message' => 'Already completed.', 'submissionId' => $submission->SubmissionId]);
        }

        $test = Test::find($submission->TestId);
        $totalQuestions = Question::where('TestId', $submission->TestId)->count();
        $answeredCount = SubmissionDetail::where('SubmissionId', $submissionId)
            ->whereNotNull('SelectedAnswerId')
            ->count();

        $isForced = $request->boolean('forcedTimeout') || $request->boolean('autoSubmit');
        if (!$isForced && $answeredCount < $totalQuestions) {
            $unanswered = $totalQuestions - $answeredCount;
            return response()->json([
                'success' => false,
                'message' => "មិនអាចបញ្ជូនការប្រឡងបានទេ! អ្នកត្រូវតែឆ្លើយសំណួរឱ្យបានគ្រប់ទាំងអស់ (នៅសល់ {$unanswered} សំណួរទៀតមិនទាន់ឆ្លើយ)។",
                'unansweredCount' => $unanswered,
            ], 422);
        }

        $totalCorrect = SubmissionDetail::where('SubmissionId', $submissionId)->where('IsCorrect', true)->count();

        $score = $totalQuestions > 0
            ? round(($totalCorrect / $totalQuestions) * $test->TotalMarks, 2)
            : 0;

        $interruptions = (int) $request->input('interruptions', 0);

        $submission->TotalCorrect = $totalCorrect;
        $submission->Score = $score;
        $submission->CompletedAt = now();

        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('tblstudentsubmission', 'Interruptions') ||
                \Illuminate\Support\Facades\Schema::hasColumn('tblStudentSubmission', 'Interruptions')) {
                $submission->Interruptions = $interruptions;
            }
        } catch (\Throwable $e) {}

        $submission->save();

        // ─── Telegram Notification Alert via Google Apps Script Cloud Bridge ───
        $telegramSent = false;
        $studentTelegramLinked = false;
        try {
            $telegramService = app(\App\Services\TelegramService::class);
            $freshStudent = \App\Models\Student::find($student->StudentId);
            if ($freshStudent) {
                $submission->setRelation('student', $freshStudent);
                $studentTelegramLinked = !empty($freshStudent->TelegramChatId);
            }
            // Dispatches result to Google Apps Script (handles both Student notification & Admin alert in one fast non-blocking request)
            $res = $telegramService->sendExamSubmissionAlert($submission);
            $telegramSent = !empty($res['ok']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Telegram exam submission alert failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'submitted' => true,
            'message' => 'ការប្រឡងត្រូវបាន Submit ជោគជ័យ (Your exam has been submitted successfully).',
            'submissionId' => $submission->SubmissionId,
            'telegramSent' => $telegramSent,
            'studentTelegramLinked' => $studentTelegramLinked,
        ]);
    }
}
