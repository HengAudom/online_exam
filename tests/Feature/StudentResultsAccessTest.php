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

class StudentResultsAccessTest extends TestCase
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

    public function test_student_can_view_their_own_result_detail(): void
    {
        $skill = Skill::create(['SkillName' => 'IT Test', 'Description' => '']);

        $student = Student::create([
            'StudentCode' => 'RTC-2026-STU1',
            'FirstName' => 'Audom',
            'LastName' => 'Heng',
            'Gender' => 'Male',
            'Phone' => '012345678',
            'SkillId' => $skill->SkillId,
        ]);

        $admin = Admin::create([
            'Username' => 'admin_results_tester',
            'Password' => Hash::make('password'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $test = Test::create([
            'SkillId' => $skill->SkillId,
            'TestName' => 'Final Exam',
            'DurationMinutes' => 30,
            'TotalMarks' => 10,
            'Status' => 'Published',
            'CreatedByUserId' => $admin->AdminId,
        ]);

        $q = Question::create([
            'TestId' => $test->TestId,
            'QuestionText' => 'What is 1+1?',
            'Points' => 10,
        ]);

        $a1 = Answer::create([
            'QuestionId' => $q->QuestionId,
            'AnswerText' => '2',
            'IsCorrect' => true,
        ]);

        $submission = StudentSubmission::create([
            'StudentId' => $student->StudentId,
            'TestId' => $test->TestId,
            'Score' => 10,
            'TotalCorrect' => 1,
            'Interruptions' => 1,
            'StartedAt' => now()->subMinutes(5),
            'CompletedAt' => now(),
        ]);

        SubmissionDetail::create([
            'SubmissionId' => $submission->SubmissionId,
            'QuestionId' => $q->QuestionId,
            'SelectedAnswerId' => $a1->AnswerId,
            'IsCorrect' => true,
        ]);

        // Access via student results endpoint
        $response = $this->actingAs($student)->getJson("/api/student/results/{$submission->SubmissionId}");

        $response->assertStatus(200);
        $response->assertJson([
            'submissionId' => $submission->SubmissionId,
            'studentId' => 'RTC-2026-STU1',
            'studentName' => 'Audom Heng',
            'testName' => 'Final Exam',
            'totalMarks' => 10,
            'score' => 10,
            'totalCorrect' => 1,
            'totalQuestions' => 1,
            'incorrect' => 0,
            'skipped' => 0,
            'interruptions' => 1,
            'accuracy' => 100,
        ]);
        $this->assertCount(1, $response->json('questions'));
    }

    public function test_student_cannot_view_another_students_result_detail(): void
    {
        $skill = Skill::create(['SkillName' => 'Science', 'Description' => '']);

        $student1 = Student::create([
            'StudentCode' => 'RTC-2026-STU1',
            'FirstName' => 'Student',
            'LastName' => 'One',
            'Gender' => 'Male',
            'Phone' => '012111111',
            'SkillId' => $skill->SkillId,
        ]);

        $student2 = Student::create([
            'StudentCode' => 'RTC-2026-STU2',
            'FirstName' => 'Student',
            'LastName' => 'Two',
            'Gender' => 'Female',
            'Phone' => '012222222',
            'SkillId' => $skill->SkillId,
        ]);

        $admin = Admin::create([
            'Username' => 'admin_test2',
            'Password' => Hash::make('password'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $test = Test::create([
            'SkillId' => $skill->SkillId,
            'TestName' => 'Science Test',
            'DurationMinutes' => 20,
            'TotalMarks' => 5,
            'Status' => 'Published',
            'CreatedByUserId' => $admin->AdminId,
        ]);

        $submission2 = StudentSubmission::create([
            'StudentId' => $student2->StudentId,
            'TestId' => $test->TestId,
            'Score' => 5,
            'TotalCorrect' => 1,
            'StartedAt' => now()->subMinutes(10),
            'CompletedAt' => now(),
        ]);

        // Student 1 tries to view Student 2's submission
        $response = $this->actingAs($student1)->getJson("/api/student/results/{$submission2->SubmissionId}");
        $response->assertStatus(403);
    }
}
