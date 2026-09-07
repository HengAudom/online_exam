<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Group;
use App\Models\Question;
use App\Models\Skill;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class TestController extends Controller
{
    /**
     * Create a test with questions and answers in a single transaction.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!AdminController::checkAdminPermission($user, 'Exams', 'create')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to create exams.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'skillId' => ['nullable', 'integer'],
            'groupId' => ['nullable', 'integer'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'totalMarks' => ['required', 'integer', 'min:1'],
            'scheduledAt' => ['nullable', 'date'],
            'finishedAt' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:Draft,Published'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.passage' => ['nullable', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.answers' => ['required', 'array', 'min:2'],
            'questions.*.answers.*.text' => ['required', 'string'],
            'questions.*.answers.*.correct' => ['required', 'boolean'],
        ]);

        $skillId = $data['skillId'] ?? Skill::first()?->SkillId ?? 1;

        DB::transaction(function () use ($data, $user, $skillId) {
            $test = Test::create([
                'SkillId' => $skillId,
                'GroupId' => $data['groupId'] ?? null,
                'CreatedByUserId' => $user->id,
                'TestName' => $data['name'],
                'DurationMinutes' => $data['durationMinutes'],
                'TotalMarks' => $data['totalMarks'],
                'ScheduledAt' => $data['scheduledAt'] ?? null,
                'FinishedAt' => $data['finishedAt'] ?? null,
                'Status' => $data['status'] ?? 'Draft',
            ]);

            foreach ($data['questions'] as $qData) {
                $isExample = (bool)($qData['isExample'] ?? $qData['is_example'] ?? (preg_match('/^(?:0\.|០\.|Example|Ex\.|គំរូ)/iu', trim($qData['text'] ?? ''))));
                $question = Question::create([
                    'TestId' => $test->TestId,
                    'QuestionText' => $qData['text'],
                    'Passage' => $qData['passage'] ?? null,
                    'IsExample' => $isExample,
                    'Points' => $qData['points'] ?? 1,
                ]);

                foreach ($qData['answers'] as $aData) {
                    Answer::create([
                        'QuestionId' => $question->QuestionId,
                        'AnswerText' => $aData['text'],
                        'IsCorrect' => $aData['correct'],
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
        $test = Test::with(['questions.answers', 'skill', 'group'])->find($id);
        if (!$test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        return response()->json([
            'test' => [
                'id' => $test->TestId,
                'name' => $test->TestName,
                'skillId' => $test->SkillId,
                'groupId' => $test->GroupId,
                'skillName' => $test->skill?->SkillName,
                'groupName' => $test->group?->GroupName,
                'durationMinutes' => $test->DurationMinutes,
                'totalMarks' => $test->TotalMarks,
                'scheduledAt' => $test->ScheduledAt?->toDateTimeString(),
                'finishedAt' => $test->FinishedAt?->toDateTimeString(),
                'status' => $test->Status,
                'questions' => $test->questions->map(function ($q) {
                    return [
                        'id' => $q->QuestionId,
                        'text' => $q->QuestionText,
                        'passage' => $q->Passage,
                        'isExample' => (bool)($q->IsExample || preg_match('/^(?:0\.|០\.|Example|Ex\.|គំរូ)/iu', trim($q->QuestionText))),
                        'points' => $q->Points,
                        'answers' => $q->answers->map(fn($a) => [
                            'id' => $a->AnswerId,
                            'text' => $a->AnswerText,
                            'correct' => $a->IsCorrect,
                        ])->values(),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Update a test, replacing its questions/answers.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!AdminController::checkAdminPermission($user, 'Exams', 'edit')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit exams.'], 403);
        }

        $test = Test::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'skillId' => ['nullable', 'integer'],
            'groupId' => ['nullable', 'integer'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'totalMarks' => ['required', 'integer', 'min:1'],
            'scheduledAt' => ['nullable', 'date'],
            'finishedAt' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:Draft,Published'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.passage' => ['nullable', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.answers' => ['required', 'array', 'min:2'],
            'questions.*.answers.*.text' => ['required', 'string'],
            'questions.*.answers.*.correct' => ['required', 'boolean'],
        ]);

        $skillId = $data['skillId'] ?? $test->SkillId ?? Skill::first()?->SkillId ?? 1;

        DB::transaction(function () use ($data, $test, $skillId) {
            $test->update([
                'SkillId' => $skillId,
                'GroupId' => $data['groupId'] ?? null,
                'TestName' => $data['name'],
                'DurationMinutes' => $data['durationMinutes'],
                'TotalMarks' => $data['totalMarks'],
                'ScheduledAt' => $data['scheduledAt'] ?? null,
                'FinishedAt' => $data['finishedAt'] ?? null,
                'Status' => $data['status'] ?? 'Draft',
            ]);

            // Delete old questions/answers and recreate
            foreach ($test->questions as $question) {
                Answer::where('QuestionId', $question->QuestionId)->delete();
            }
            Question::where('TestId', $test->TestId)->delete();

            foreach ($data['questions'] as $qData) {
                $isExample = (bool)($qData['isExample'] ?? $qData['is_example'] ?? (preg_match('/^(?:0\.|០\.|Example|Ex\.|គំរូ)/iu', trim($qData['text'] ?? ''))));
                $question = Question::create([
                    'TestId' => $test->TestId,
                    'QuestionText' => $qData['text'],
                    'Passage' => $qData['passage'] ?? null,
                    'IsExample' => $isExample,
                    'Points' => $qData['points'] ?? 1,
                ]);

                foreach ($qData['answers'] as $aData) {
                    Answer::create([
                        'QuestionId' => $question->QuestionId,
                        'AnswerText' => $aData['text'],
                        'IsCorrect' => $aData['correct'],
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
        $user = $request->user();
        if (!AdminController::checkAdminPermission($user, 'Exams', 'delete')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to delete exams.'], 403);
        }

        $test = Test::find($id);
        if (!$test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

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
     * Export the test details and questions/answers to a Word document (.docx).
     */
    public function exportWord(Request $request, $id)
    {
        $user = $request->user();
        if (!AdminController::checkAdminPermission($user, 'Exams', 'export')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to export exams.'], 403);
        }
        $test = Test::with(['questions.answers', 'skill', 'group'])->find($id);
        if (!$test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        $testData = [
            'TestName' => $test->TestName,
            'SkillName' => $test->skill?->SkillName ?? 'ទូទៅ',
            'GroupName' => $test->group?->GroupName ?? 'គ្រប់ក្រុម',
            'DurationMinutes' => $test->DurationMinutes,
            'TotalMarks' => $test->TotalMarks,
            'questions' => $test->questions->map(function ($q) {
                return [
                    'QuestionText' => $q->QuestionText,
                    'Passage' => $q->Passage,
                    'Points' => $q->Points,
                    'answers' => $q->answers->map(function ($a) {
                        return [
                            'AnswerText' => $a->AnswerText,
                            'IsCorrect' => (bool)$a->IsCorrect,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        $jsonTemp = tempnam(sys_get_temp_dir(), 'exam_') . '.json';
        file_put_contents($jsonTemp, json_encode($testData, JSON_UNESCAPED_UNICODE));

        $fileName = 'Exam_' . preg_replace('/[^A-Za-z0-9_\-\x{1780}-\x{17FF}]/u', '_', $test->TestName) . '.docx';
        $docxTemp = tempnam(sys_get_temp_dir(), 'exam_') . '.docx';

        $scriptPath = base_path('app/Services/export_docx.py');
        if (file_exists($scriptPath)) {
            exec("python \"{$scriptPath}\" \"{$jsonTemp}\" \"{$docxTemp}\"", $output, $retCode);
            @unlink($jsonTemp);

            if ($retCode === 0 && file_exists($docxTemp) && filesize($docxTemp) > 0) {
                return response()->download($docxTemp, $fileName)->deleteFileAfterSend(true);
            }
        }

        return $this->exportTxt($request, $id);
    }

    /**
     * Export the test details and questions/answers to a clean Text (.txt) file.
     */
    public function exportTxt(Request $request, $id)
    {
        $user = $request->user();
        if (!AdminController::checkAdminPermission($user, 'Exams', 'export')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to export exams.'], 403);
        }
        $test = Test::with(['questions.answers', 'skill', 'group'])->find($id);
        if (!$test) {
            return response()->json(['message' => 'Test not found.'], 404);
        }

        $khmerDigits = ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩'];
        $toKhmerNum = function ($num) use ($khmerDigits) {
            return strtr((string)$num, $khmerDigits);
        };
        $khmerLetters = ['ក', 'ខ', 'គ', 'ឃ', 'ង', 'ច'];

        $lines = [];
        $lines[] = "=== វិញ្ញាសា៖ " . $test->TestName . " ===";
        $lines[] = "សេចក្តីណែនាំ៖ វិញ្ញាសានេះមាន " . $toKhmerNum($test->questions->count()) . " សំណួរ ដោយសំណួរនីមួយៗមានពិន្ទុ " . $toKhmerNum($test->questions->first()?->Points ?? 4) . " (ពិន្ទុសរុប " . $toKhmerNum($test->TotalMarks) . ")។ ចម្លើយត្រឹមត្រូវត្រូវបានសម្គាល់ដោយសញ្ញា (*)";
        $lines[] = "";

        $lastPassage = null;
        foreach ($test->questions as $index => $q) {
            if ($q->Passage && $q->Passage !== $lastPassage) {
                $lines[] = "--------------------------------------------------";
                $lines[] = "[អត្ថបទអាន / Reading Passage]:";
                $lines[] = $q->Passage;
                $lines[] = "--------------------------------------------------";
                $lines[] = "";
                $lastPassage = $q->Passage;
            }

            $numKh = $toKhmerNum($index + 1);
            $ptsKh = $toKhmerNum($q->Points);
            $lines[] = "{$numKh}. ({$ptsKh}ពិន្ទុ) " . $q->QuestionText;

            foreach ($q->answers as $aIndex => $ans) {
                $letter = $khmerLetters[$aIndex] ?? chr(65 + $aIndex);
                $star = $ans->IsCorrect ? ' *' : '';
                $lines[] = "   {$letter}. " . $ans->AnswerText . $star;
            }
            $lines[] = "";
        }

        $content = implode("\r\n", $lines);
        $fileName = 'Exam_' . preg_replace('/[^A-Za-z0-9_\-\x{1780}-\x{17FF}]/u', '_', $test->TestName) . '.txt';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Parse an uploaded Word (.docx, .doc) or Text (.txt) file into raw question text.
     */
    public function parseDoc(Request $request)
    {
        $user = $request->user();
        if (!AdminController::checkAdminPermission($user, 'Exams', 'create') && !AdminController::checkAdminPermission($user, 'Exams', 'edit')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to import questions.'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $origName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($origName, PATHINFO_EXTENSION));
        $text = '';

        if ($ext === 'txt') {
            $text = file_get_contents($file->getRealPath());
        } elseif ($ext === 'docx') {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) === true) {
                if (($xmlIndex = $zip->locateName('word/document.xml')) !== false) {
                    $xmlData = $zip->getFromIndex($xmlIndex);
                    $dom = new \DOMDocument();
                    @$dom->loadXML($xmlData);
                    $xpath = new \DOMXPath($dom);
                    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');
                    
                    $paragraphs = [];
                    foreach ($xpath->query('//w:p') as $p) {
                        $pText = '';
                        foreach ($p->childNodes as $child) {
                            $cTag = $child->localName;
                            if ($cTag === 'r') {
                                foreach ($child->childNodes as $rChild) {
                                    $rTag = $rChild->localName;
                                    if ($rTag === 't') {
                                        $pText .= $rChild->nodeValue;
                                    } elseif ($rTag === 'tab') {
                                        $pText .= "\t";
                                    } elseif ($rTag === 'br') {
                                        $pText .= "\n";
                                    }
                                }
                            } elseif ($cTag === 'oMath' || $cTag === 'oMathPara') {
                                $latexMath = trim($this->ommlToText($child));
                                if ($latexMath !== '') {
                                    $pText .= ' $' . $latexMath . '$ ';
                                }
                            }
                        }
                        $trimmed = trim($pText);
                        if ($trimmed !== '') {
                            $paragraphs[] = $trimmed;
                        }
                    }
                    $text = implode("\n", $paragraphs);
                }
                $zip->close();
            }
        }

        // Fallback: If zip extraction returned empty or file is .doc
        if (empty(trim($text))) {
            try {
                $phpWord = IOFactory::load($file->getRealPath());
                $paragraphs = [];
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        if (method_exists($element, 'getText')) {
                            $t = $element->getText();
                            if (is_string($t) && trim($t) !== '') {
                                $paragraphs[] = trim($t);
                            }
                        }
                    }
                }
                $text = implode("\n", $paragraphs);
            } catch (\Throwable $e) {
                // If IOFactory also failed, try reading utf-8 string chunks
                $raw = @file_get_contents($file->getRealPath());
                if ($raw) {
                    $text = preg_replace('/[^\x20-\x7E\x{1780}-\x{17FF}\x{19E0}-\x{19FF}\n\r\t]/u', ' ', $raw);
                }
            }
        }

        return response()->json([
            'success' => true,
            'fileName' => $origName,
            'text' => trim($text)
        ]);
    }

    /**
     * Convert Office Math Markup Language (OMML) XML node to clean LaTeX representation.
     */
    private function ommlToText($node)
    {
        if (!$node) return '';
        $tag = $node->localName;

        if ($tag === 't') {
            return $node->nodeValue;
        }

        if ($tag === 'r') {
            $res = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName !== 'rPr') {
                    $res .= $this->ommlToText($c);
                }
            }
            return $res;
        }

        if ($tag === 'f') {
            $num = ''; $den = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName === 'num') $num = $this->ommlToText($c);
                if ($c->localName === 'den') $den = $this->ommlToText($c);
            }
            return '\\frac{' . trim($num) . '}{' . trim($den) . '}';
        }

        if ($tag === 'rad') {
            $deg = ''; $e = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName === 'deg') $deg = $this->ommlToText($c);
                if ($c->localName === 'e') $e = $this->ommlToText($c);
            }
            if (trim($deg) !== '') return '\\sqrt[' . trim($deg) . ']{' . trim($e) . '}';
            return '\\sqrt{' . trim($e) . '}';
        }

        if ($tag === 'sSup') {
            $e = ''; $sup = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName === 'e') $e = $this->ommlToText($c);
                if ($c->localName === 'sup') $sup = $this->ommlToText($c);
            }
            return '{' . trim($e) . '}^{' . trim($sup) . '}';
        }

        if ($tag === 'sSub') {
            $e = ''; $sub = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName === 'e') $e = $this->ommlToText($c);
                if ($c->localName === 'sub') $sub = $this->ommlToText($c);
            }
            return '{' . trim($e) . '}_{' . trim($sub) . '}';
        }

        if ($tag === 'sSubSup') {
            $e = ''; $sub = ''; $sup = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName === 'e') $e = $this->ommlToText($c);
                if ($c->localName === 'sub') $sub = $this->ommlToText($c);
                if ($c->localName === 'sup') $sup = $this->ommlToText($c);
            }
            return '{' . trim($e) . '}_{' . trim($sub) . '}^{' . trim($sup) . '}';
        }

        if ($tag === 'nary') {
            $op = '∫'; $sub = ''; $sup = ''; $e = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName === 'naryPr') {
                    foreach ($c->childNodes as $p) {
                        if ($p->localName === 'chr') {
                            $op = $p->getAttribute('m:val') ?: $p->getAttribute('val') ?: '∫';
                        }
                    }
                }
                if ($c->localName === 'sub') $sub = $this->ommlToText($c);
                if ($c->localName === 'sup') $sup = $this->ommlToText($c);
                if ($c->localName === 'e') $e = $this->ommlToText($c);
            }
            $latexOp = ($op === '∫') ? '\\int' : $op;
            $res = $latexOp;
            if (trim($sub) !== '') $res .= '_{' . trim($sub) . '}';
            if (trim($sup) !== '') $res .= '^{' . trim($sup) . '}';
            return $res . ' ' . trim($e);
        }

        if ($tag === 'd') {
            $e = '';
            foreach ($node->childNodes as $c) {
                if ($c->localName === 'e') $e .= $this->ommlToText($c);
            }
            return '(' . trim($e) . ')';
        }

        if ($node->childNodes) {
            foreach ($node->childNodes as $child) {
                $res .= $this->ommlToText($child);
            }
        }
        return $res;
    }
}
