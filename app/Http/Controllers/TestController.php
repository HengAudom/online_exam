<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Skill;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    /**
     * Create a test with questions and answers in a single transaction.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'skillId'         => ['required', 'integer'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'totalMarks'      => ['required', 'integer', 'min:1'],
            'scheduledAt'     => ['nullable', 'date'],
            'finishedAt'      => ['nullable', 'date'],
            'status'          => ['nullable', 'string', 'in:Draft,Published'],
            'questions'       => ['required', 'array', 'min:1'],
            'questions.*.text'              => ['required', 'string'],
            'questions.*.points'            => ['required', 'integer', 'min:1'],
            'questions.*.answers'           => ['required', 'array', 'min:2'],
            'questions.*.answers.*.text'    => ['required', 'string'],
            'questions.*.answers.*.correct' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $user) {
            $test = Test::create([
                'SkillId'         => $data['skillId'],
                'BatchId'         => $data['batchId'] ?? null,
                'CreatedByUserId' => $user->id,
                'TestName'        => $data['name'],
                'DurationMinutes' => $data['durationMinutes'],
                'TotalMarks'      => $data['totalMarks'],
                'ScheduledAt'     => $data['scheduledAt'] ?? null,
                'FinishedAt'      => $data['finishedAt'] ?? null,
                'Status'          => $data['status'] ?? 'Draft',
            ]);

            foreach ($data['questions'] as $qData) {
                $question = Question::create([
                    'TestId'       => $test->TestId,
                    'QuestionText' => $qData['text'],
                    'Points'       => $qData['points'] ?? 1,
                ]);

                foreach ($qData['answers'] as $aData) {
                    Answer::create([
                        'QuestionId' => $question->QuestionId,
                        'AnswerText' => $aData['text'],
                        'IsCorrect'  => $aData['correct'],
                    ]);
                }
            }

            return $test;
        });

        return response()->json(['message' => 'Test created successfully.'], 201);
    }

    /**
     * Return a single test with questions and answers (for editing).
     */
    public function show(Request $request, $id)
    {
        $test = Test::with(['questions.answers', 'skill'])->find($id);
        if (! $test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        return response()->json([
            'test' => [
                'id'              => $test->TestId,
                'name'            => $test->TestName,
                'skillId'         => $test->SkillId,
                'skill'           => $test->skill?->SkillName,
                'batchId'         => $test->BatchId,
                'batch'           => $test->batch?->BatchName,
                'durationMinutes' => $test->DurationMinutes,
                'totalMarks'      => $test->TotalMarks,
                'scheduledAt'     => $test->ScheduledAt?->toDateTimeString(),
                'finishedAt'      => $test->FinishedAt?->toDateTimeString(),
                'status'          => $test->Status,
                'questions'       => $test->questions->map(function ($q) {
                    return [
                        'id'      => $q->QuestionId,
                        'text'    => $q->QuestionText,
                        'points'  => $q->Points,
                        'answers' => $q->answers->map(fn($a) => [
                            'id'      => $a->AnswerId,
                            'text'    => $a->AnswerText,
                            'correct' => $a->IsCorrect,
                        ])->values(),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Update a test with questions and answers.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $test = Test::findOrFail($id);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'skillId'         => ['required', 'integer'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'totalMarks'      => ['required', 'integer', 'min:1'],
            'scheduledAt'     => ['nullable', 'date'],
            'finishedAt'      => ['nullable', 'date'],
            'batchId'         => ['nullable', 'integer'],
            'status'          => ['nullable', 'string', 'in:Draft,Published'],
            'questions'       => ['required', 'array', 'min:1'],
            'questions.*.text'              => ['required', 'string'],
            'questions.*.points'            => ['required', 'integer', 'min:1'],
            'questions.*.answers'           => ['required', 'array', 'min:2'],
            'questions.*.answers.*.text'    => ['required', 'string'],
            'questions.*.answers.*.correct' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $test) {
            $test->update([
                'SkillId'         => $data['skillId'],
                'BatchId'         => $data['batchId'] ?? null,
                'TestName'        => $data['name'],
                'DurationMinutes' => $data['durationMinutes'],
                'TotalMarks'      => $data['totalMarks'],
                'ScheduledAt'     => $data['scheduledAt'] ?? null,
                'FinishedAt'      => $data['finishedAt'] ?? null,
                'Status'          => $data['status'] ?? 'Draft',
            ]);

            // Delete old questions/answers and recreate
            foreach ($test->questions as $question) {
                Answer::where('QuestionId', $question->QuestionId)->delete();
            }
            Question::where('TestId', $test->TestId)->delete();

            foreach ($data['questions'] as $qData) {
                $question = Question::create([
                    'TestId'       => $test->TestId,
                    'QuestionText' => $qData['text'],
                    'Points'       => $qData['points'] ?? 1,
                ]);

                foreach ($qData['answers'] as $aData) {
                    Answer::create([
                        'QuestionId' => $question->QuestionId,
                        'AnswerText' => $aData['text'],
                        'IsCorrect'  => $aData['correct'],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Test updated successfully.']);
    }

    /**
     * Delete a test and cascade.
     */
    public function destroy(Request $request, $id)
    {
        $test = Test::find($id);
        if (! $test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        // Delete answers → questions → test manually (SQLite may lack cascade)
        DB::transaction(function () use ($test) {
            foreach ($test->questions as $question) {
                Answer::where('QuestionId', $question->QuestionId)->delete();
            }
            Question::where('TestId', $test->TestId)->delete();
            $test->delete();
        });

        return response()->json(['message' => 'Test deleted.']);
    }

    /**
     * Export a single test as a downloadable JSON file.
     */
    public function export(Request $request, $id)
    {
        $test = Test::with(['questions.answers', 'skill', 'batch'])->find($id);
        if (! $test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        $payload = [
            '_export_version' => '1.0',
            'name'            => $test->TestName,
            'skill'           => $test->skill?->SkillName,
            'durationMinutes' => $test->DurationMinutes,
            'totalMarks'      => $test->TotalMarks,
            'scheduledAt'     => $test->ScheduledAt?->toDateTimeString(),
            'finishedAt'      => $test->FinishedAt?->toDateTimeString(),
            'questions'       => $test->questions->map(function ($q) {
                return [
                    'text'    => $q->QuestionText,
                    'points'  => $q->Points,
                    'answers' => $q->answers->map(fn($a) => [
                        'text'    => $a->AnswerText,
                        'correct' => (bool) $a->IsCorrect,
                    ])->values(),
                ];
            })->values(),
        ];

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $test->TestName) . '_export.json';

        return response()->json($payload)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Import a test from a JSON file upload.
     */
    public function import(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'file'    => ['required', 'file', 'mimes:json,txt', 'max:2048'],
            'skillId' => ['required', 'integer'],
            'batchId' => ['nullable', 'integer'],
            'status'  => ['nullable', 'string', 'in:Draft,Published'],
        ]);

        $contents = file_get_contents($request->file('file')->getRealPath());
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['questions'])) {
            return response()->json(['message' => 'Invalid JSON file format.'], 422);
        }

        try {
            DB::transaction(function () use ($data, $request, $user) {
                $test = Test::create([
                    'SkillId'         => $request->input('skillId'),
                    'BatchId'         => $request->input('batchId') ?? null,
                    'CreatedByUserId' => $user->id,
                    'TestName'        => ($data['name'] ?? 'Imported Test') . ' (Copy)',
                    'DurationMinutes' => $data['durationMinutes'] ?? 45,
                    'TotalMarks'      => $data['totalMarks'] ?? 0,
                    'ScheduledAt'     => $data['scheduledAt'] ?? null,
                    'FinishedAt'      => $data['finishedAt'] ?? null,
                    'Status'          => $request->input('status', 'Draft'),
                ]);

                foreach ($data['questions'] as $qData) {
                    $question = Question::create([
                        'TestId'       => $test->TestId,
                        'QuestionText' => $qData['text'],
                        'Points'       => $qData['points'] ?? 1,
                    ]);

                    foreach ($qData['answers'] as $aData) {
                        Answer::create([
                            'QuestionId' => $question->QuestionId,
                            'AnswerText' => $aData['text'],
                            'IsCorrect'  => $aData['correct'] ?? false,
                        ]);
                    }
                }
            });

            return response()->json(['message' => 'Test imported successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}
