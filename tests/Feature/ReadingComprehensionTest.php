<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSubmission;
use App\Models\SubmissionDetail;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReadingComprehensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $permsFile = storage_path('app/permissions.json');
        if (file_exists($permsFile)) {
            @unlink($permsFile);
        }
    }

    protected function tearDown(): void
    {
        $permsFile = storage_path('app/permissions.json');
        if (file_exists($permsFile)) {
            @unlink($permsFile);
        }
        parent::tearDown();
    }

    public function test_can_create_and_fetch_test_with_reading_passage()
    {
        $admin = Admin::create([
            'Username' => 'admin_reading',
            'Password' => Hash::make('pass'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $skill = Skill::create(['SkillName' => 'English', 'Description' => 'Language']);

        $passageText = "Text: Linda's Life\nLinda is a high school student living in the city.";

        $payload = [
            'name' => 'English Entrance Exam',
            'skillId' => $skill->SkillId,
            'durationMinutes' => 60,
            'totalMarks' => 10,
            'status' => 'Published',
            'questions' => [
                [
                    'text' => 'What time does Linda usually wake up?',
                    'passage' => $passageText,
                    'points' => 1,
                    'answers' => [
                        ['text' => '5:00', 'correct' => false],
                        ['text' => '6:00', 'correct' => true],
                    ],
                ],
                [
                    'text' => 'Who does Linda have breakfast with?',
                    'passage' => $passageText,
                    'points' => 1,
                    'answers' => [
                        ['text' => 'Her friends', 'correct' => false],
                        ['text' => 'Her family', 'correct' => true],
                    ],
                ],
            ],
        ];

        $res = $this->actingAs($admin)->postJson('/api/admin/tests', $payload);
        $res->assertStatus(201);

        $test = Test::where('TestName', 'English Entrance Exam')->first();
        $this->assertNotNull($test);

        // Fetch test for edit
        $showRes = $this->actingAs($admin)->getJson("/api/admin/tests/{$test->TestId}");
        $showRes->assertStatus(200);
        $showData = $showRes->json('test');
        $this->assertEquals($passageText, $showData['questions'][0]['passage']);
        $this->assertEquals($passageText, $showData['questions'][1]['passage']);
    }

    public function test_student_receives_passage_during_exam_and_results()
    {
        $skill = Skill::create(['SkillName' => 'Vocational', 'Description' => '']);
        $student = Student::create([
            'StudentCode' => 'STU9988',
            'FirstName' => 'Chanthy',
            'LastName' => 'Sok',
            'Gender' => 'Female',
            'Phone' => '012345678',
            'SkillId' => $skill->SkillId,
        ]);

        $admin = Admin::create([
            'Username' => 'admin_reading_exam',
            'Password' => Hash::make('pass'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $passage = "Text: Sokha's Preparation\nSokha is a vocational student.";

        $test = Test::create([
            'SkillId' => $skill->SkillId,
            'TestName' => 'Scholarship Reading Exam',
            'DurationMinutes' => 60,
            'TotalMarks' => 4,
            'Status' => 'Published',
            'CreatedByUserId' => $admin->AdminId,
        ]);

        $q = Question::create([
            'TestId' => $test->TestId,
            'QuestionText' => 'Where does Sokha live?',
            'Passage' => $passage,
            'Points' => 2,
        ]);

        $a1 = Answer::create(['QuestionId' => $q->QuestionId, 'AnswerText' => 'In a small town', 'IsCorrect' => true]);
        $a2 = Answer::create(['QuestionId' => $q->QuestionId, 'AnswerText' => 'In a big city', 'IsCorrect' => false]);

        // Student starts exam
        $startRes = $this->actingAs($student)->getJson("/api/exam/{$test->TestId}/start");
        $startRes->assertStatus(200);
        $questions = $startRes->json('questions');
        $this->assertNotEmpty($questions);
        $this->assertEquals($passage, $questions[0]['passage']);

        // Student submits answer
        $sub = StudentSubmission::where('StudentId', $student->StudentId)->where('TestId', $test->TestId)->first();
        $sub->update(['CompletedAt' => now(), 'Score' => 2]);
        SubmissionDetail::create([
            'SubmissionId' => $sub->SubmissionId,
            'QuestionId' => $q->QuestionId,
            'SelectedAnswerId' => $a1->AnswerId,
            'IsCorrect' => true,
        ]);

        // Fetch result details as Admin
        $resultRes = $this->actingAs($admin)->getJson("/api/admin/results/{$sub->SubmissionId}");
        $resultRes->assertStatus(200);
        $resQuestions = $resultRes->json('questions');
        $this->assertEquals($passage, $resQuestions[0]['passage']);
    }

    public function test_example_question_is_automatically_pre_answered()
    {
        $skill = Skill::create(['SkillName' => 'General Studies', 'Description' => '']);
        $student = Student::create([
            'StudentCode' => 'STU8877',
            'FirstName' => 'Sara',
            'LastName' => 'Chan',
            'Gender' => 'Female',
            'Phone' => '012999888',
            'SkillId' => $skill->SkillId,
        ]);

        $admin = Admin::create([
            'Username' => 'admin_example_q',
            'Password' => Hash::make('pass'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $test = Test::create([
            'SkillId' => $skill->SkillId,
            'TestName' => 'English Exam With Example Question',
            'DurationMinutes' => 60,
            'TotalMarks' => 10,
            'Status' => 'Published',
            'CreatedByUserId' => $admin->AdminId,
        ]);

        // Example question 0
        $q0 = Question::create([
            'TestId' => $test->TestId,
            'QuestionText' => '0. Sara studies English with his teacher ______________.',
            'IsExample' => true,
            'Points' => 1,
        ]);
        $a1 = Answer::create(['QuestionId' => $q0->QuestionId, 'AnswerText' => 'in the kitchen', 'IsCorrect' => false]);
        $a2 = Answer::create(['QuestionId' => $q0->QuestionId, 'AnswerText' => 'at school', 'IsCorrect' => true]);

        // Real question 1
        $q1 = Question::create([
            'TestId' => $test->TestId,
            'QuestionText' => '1. Next week the teacher will give a test.',
            'IsExample' => false,
            'Points' => 1,
        ]);
        $a3 = Answer::create(['QuestionId' => $q1->QuestionId, 'AnswerText' => 'lunch', 'IsCorrect' => false]);
        $a4 = Answer::create(['QuestionId' => $q1->QuestionId, 'AnswerText' => 'exam', 'IsCorrect' => true]);

        // Start exam as student
        $res = $this->actingAs($student)->getJson("/api/exam/{$test->TestId}/start");
        $res->assertStatus(200);
        $questions = $res->json('questions');

        // Question 0 should have isExample: true and defaultAnswerId: $a2->AnswerId
        $this->assertTrue($questions[0]['isExample']);
        $this->assertEquals($a2->AnswerId, $questions[0]['defaultAnswerId']);

        // Question 1 should have isExample: false and defaultAnswerId: null
        $this->assertFalse($questions[1]['isExample']);
        $this->assertNull($questions[1]['defaultAnswerId']);

        // Check that SubmissionDetail was pre-created for Q0
        $sub = StudentSubmission::where('StudentId', $student->StudentId)->where('TestId', $test->TestId)->first();
        $this->assertNotNull($sub);
        $detail0 = SubmissionDetail::where('SubmissionId', $sub->SubmissionId)->where('QuestionId', $q0->QuestionId)->first();
        $this->assertNotNull($detail0);
        $this->assertEquals($a2->AnswerId, $detail0->SelectedAnswerId);
    }
}
