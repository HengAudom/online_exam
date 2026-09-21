<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Answer;
use App\Models\Group;
use App\Models\Question;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentSubmission;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningRemediationTest extends TestCase
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

    public function test_telegram_student_results_rejects_unauthenticated(): void
    {
        $response = $this->getJson('/api/telegram/student-results?code=RTC-2026-0001');
        $response->assertStatus(401);
    }

    public function test_telegram_submission_questions_rejects_unauthenticated(): void
    {
        $response = $this->getJson('/api/telegram/submission-questions?submissionId=1');
        $response->assertStatus(401);
    }

    public function test_telegram_sync_link_and_sync_unlink_require_authorization(): void
    {
        $resLink = $this->postJson('/api/telegram/sync-link', [
            'studentCode' => 'RTC-2026-0001',
            'chatId' => '12345678',
        ]);
        $resLink->assertStatus(401);

        $resUnlink = $this->postJson('/api/telegram/sync-unlink', [
            'chatId' => '12345678',
        ]);
        $resUnlink->assertStatus(401);
    }

    public function test_telegram_webhook_requires_secret_token(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345],
                'text' => '/start'
            ]
        ]);
        $response->assertStatus(401);
    }

    public function test_student_login_is_passwordless_by_student_id(): void
    {
        $skill = Skill::create(['SkillName' => 'Computer', 'Description' => '']);
        $group = Group::create(['GroupName' => 'A1']);

        $student = Student::create([
            'StudentCode' => 'RTC-2026-99999',
            'FirstName' => 'Sok',
            'LastName' => 'Dara',
            'Gender' => 'Male',
            'Phone' => '012345678',
            'SkillId' => $skill->SkillId,
            'GroupId' => $group->GroupId,
        ]);

        // Login with student ID only (no password)
        $response = $this->postJson('/api/login', [
            'identifier' => 'RTC-2026-99999',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'loginType' => 'student',
            'role' => 'Student',
        ]);
        $this->assertAuthenticatedAs($student);
    }

    public function test_admin_phone_verification_requires_exact_normalized_match(): void
    {
        Admin::create([
            'Username' => 'admin_test_phone',
            'FirstName' => 'Audom',
            'LastName' => 'Heng',
            'Password' => Hash::make('secret123'),
            'Phone' => '012999888',
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        // Partial match with ends_with should FAIL
        $resFail = $this->postJson('/api/password/verify-identity', [
            'username' => 'admin_test_phone',
            'phone' => '888',
        ]);
        $resFail->assertStatus(422);

        // Exact match with different prefix (85512999888 vs 012999888) should SUCCEED
        $resSuccess = $this->postJson('/api/password/verify-identity', [
            'username' => 'admin_test_phone',
            'phone' => '85512999888',
        ]);
        $resSuccess->assertStatus(200);
        $resSuccess->assertJson(['username' => 'admin_test_phone']);

        // Verification by full name "Audom Heng" should also SUCCEED
        $resFullName = $this->postJson('/api/password/verify-identity', [
            'username' => 'Audom Heng',
            'phone' => '012999888',
        ]);
        $resFullName->assertStatus(200);
        $resFullName->assertJson(['username' => 'admin_test_phone']);
    }

    public function test_exam_save_answer_validates_question_and_answer_belong_to_test(): void
    {
        $skill = Skill::create(['SkillName' => 'Networking', 'Description' => '']);
        $student = Student::create([
            'StudentCode' => 'RTC-2026-0005',
            'FirstName' => 'Test',
            'LastName' => 'Student',
            'SkillId' => $skill->SkillId,
        ]);

        $admin = Admin::create([
            'Username' => 'admin_exam_test',
            'Password' => Hash::make('password'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $testA = Test::create([
            'SkillId' => $skill->SkillId,
            'TestName' => 'Exam A',
            'DurationMinutes' => 30,
            'TotalMarks' => 10,
            'Status' => 'Published',
            'CreatedByUserId' => $admin->AdminId,
        ]);

        $testB = Test::create([
            'SkillId' => $skill->SkillId,
            'TestName' => 'Exam B',
            'DurationMinutes' => 30,
            'TotalMarks' => 10,
            'Status' => 'Published',
            'CreatedByUserId' => $admin->AdminId,
        ]);

        $qA = Question::create([
            'TestId' => $testA->TestId,
            'QuestionText' => 'Question in A',
        ]);
        $ansA1 = Answer::create([
            'QuestionId' => $qA->QuestionId,
            'AnswerText' => 'Answer A1',
            'IsCorrect' => true,
        ]);

        $qB = Question::create([
            'TestId' => $testB->TestId,
            'QuestionText' => 'Question in B',
        ]);
        $ansB1 = Answer::create([
            'QuestionId' => $qB->QuestionId,
            'AnswerText' => 'Answer B1',
            'IsCorrect' => true,
        ]);

        $submission = StudentSubmission::create([
            'StudentId' => $student->StudentId,
            'TestId' => $testA->TestId,
            'Status' => 'In Progress',
            'StartedAt' => now(),
        ]);

        $this->actingAs($student);

        // Attempt to answer a question that belongs to Test B during Test A exam
        $responseWrongQuestion = $this->postJson('/api/exam/answer', [
            'submissionId' => $submission->SubmissionId,
            'questionId' => $qB->QuestionId,
            'selectedAnswerId' => $ansB1->AnswerId,
        ]);
        $responseWrongQuestion->assertStatus(422);

        // Attempt to select an answer from Question B for Question A
        $responseWrongAnswer = $this->postJson('/api/exam/answer', [
            'submissionId' => $submission->SubmissionId,
            'questionId' => $qA->QuestionId,
            'selectedAnswerId' => $ansB1->AnswerId,
        ]);
        $responseWrongAnswer->assertStatus(422);

        // Legitimate answer
        $responseOk = $this->postJson('/api/exam/answer', [
            'submissionId' => $submission->SubmissionId,
            'questionId' => $qA->QuestionId,
            'selectedAnswerId' => $ansA1->AnswerId,
        ]);
        $responseOk->assertStatus(200);
    }

    public function test_change_password_hashes_and_saves_correctly(): void
    {
        $admin = Admin::create([
            'Username' => 'admin_change_pw',
            'Password' => Hash::make('oldpassword123'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $this->actingAs($admin);

        $response = $this->postJson('/api/profile/change-password', [
            'currentPassword' => 'oldpassword123',
            'newPassword' => 'newpassword456',
            'newPassword_confirmation' => 'newpassword456',
        ]);

        $response->assertStatus(200);

        $admin->refresh();
        $this->assertTrue(Hash::check('newpassword456', $admin->Password));
    }

    public function test_security_headers_are_applied(): void
    {
        $response = $this->get('/api/public-settings');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->assertFalse($response->headers->has('X-XSS-Protection'));
    }

    public function test_unauthenticated_requests_to_apis_are_strictly_rejected_with_401(): void
    {
        $endpoints = [
            ['GET', '/api/public-settings'],
            ['GET', '/api/skills-groups'],
            ['GET', '/api/profile'],
            ['POST', '/api/profile/update'],
            ['GET', '/api/lucky-wheel/remote/state'],
            ['POST', '/api/lucky-wheel/remote/command'],
            ['GET', '/api/lucky-wheel/remote/ping'],
            ['GET', '/api/lucky-wheel/remote/room'],
            ['GET', '/api/exam/1/start'],
            ['GET', '/api/student/results'],
            ['GET', '/api/admin/dashboard'],
            ['GET', '/api/admin/students'],
            ['GET', '/api/admin/tests'],
            ['GET', '/api/admin/system-settings'],
            ['GET', '/api/unknown-endpoint-test'],
            ['POST', '/api/some/random/action'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertStatus(401);
            $response->assertJson(['message' => 'Unauthenticated.']);
        }
    }

    public function test_clear_audit_logs_succeeds_without_error(): void
    {
        $superAdmin = Admin::create([
            'Username' => 'superadmin_clear_logs',
            'Password' => Hash::make('password123'),
            'Role' => 'Super Admin',
            'Status' => 'Active',
        ]);

        $this->actingAs($superAdmin);

        $response = $this->deleteJson('/api/admin/audit-logs');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'All audit logs cleared successfully.'
        ]);
    }
}
