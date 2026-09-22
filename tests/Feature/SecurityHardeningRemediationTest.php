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
            ['GET', '/api/profile'],
            ['POST', '/api/profile/update'],
            ['POST', '/api/skills-groups'],
            ['POST', '/api/admin/skills'],
            ['PUT', '/api/admin/skills/1'],
            ['DELETE', '/api/admin/skills/1'],
            ['POST', '/api/admin/groups'],
            ['PUT', '/api/admin/groups/1'],
            ['DELETE', '/api/admin/groups/1'],
            ['POST', '/api/admin/durations'],
            ['PUT', '/api/admin/durations/1'],
            ['DELETE', '/api/admin/durations/1'],
            ['GET', '/api/lucky-wheel/remote/state'],
            ['POST', '/api/lucky-wheel/remote/command'],
            ['GET', '/api/lucky-wheel/remote/ping'],
            ['GET', '/api/lucky-wheel/remote/room'],
            ['GET', '/api/exam/1/start'],
            ['GET', '/api/student/results'],
            ['GET', '/api/admin/dashboard'],
            ['GET', '/api/admin/students'],
            ['GET', '/api/admin/admins'],
            ['POST', '/api/admin/admins'],
            ['PUT', '/api/admin/admins/1'],
            ['DELETE', '/api/admin/admins/1'],
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

    public function test_login_prevents_username_enumeration(): void
    {
        // 1. Existing Admin with wrong password
        \App\Models\Admin::create([
            'Username' => 'secadmin',
            'Password' => \Illuminate\Support\Facades\Hash::make('Secret123!'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        $resExisting = $this->postJson('/api/login', [
            'identifier' => 'secadmin',
            'password' => 'WrongPassword999',
            'lang' => 'en'
        ]);
        $resExisting->assertStatus(422);
        $resExisting->assertJson(['message' => 'Invalid credentials.']);

        // 2. Non-existent username with random password
        $resNonExistent = $this->postJson('/api/login', [
            'identifier' => 'nobody_exist_xyz123',
            'password' => 'WrongPassword999',
            'lang' => 'en'
        ]);
        $resNonExistent->assertStatus(422);
        $resNonExistent->assertJson(['message' => 'Invalid credentials.']);

        // Assert identical error messages
        $this->assertEquals($resExisting->json('message'), $resNonExistent->json('message'));
    }

    public function test_skills_groups_endpoint_respects_registration_status(): void
    {
        // 1. When self-registration is disabled, guest access is forbidden (403)
        \Illuminate\Support\Facades\Cache::forever('system_settings', ['allowRegistration' => false]);
        $responseDisabled = $this->getJson('/api/skills-groups');
        $responseDisabled->assertStatus(403);

        // 2. When self-registration is enabled, guest access is allowed (200)
        \Illuminate\Support\Facades\Cache::forever('system_settings', ['allowRegistration' => true]);
        $responseEnabled = $this->getJson('/api/skills-groups');
        $responseEnabled->assertStatus(200);
        $responseEnabled->assertJsonStructure(['skills', 'groups', 'durations']);
    }

    public function test_csp_does_not_contain_unsafe_eval_and_rate_limit_headers_are_stripped(): void
    {
        $response = $this->get('/api/public-settings');
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotEmpty($csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);

        $this->assertFalse($response->headers->has('X-Ratelimit-Limit'));
        $this->assertFalse($response->headers->has('X-Ratelimit-Remaining'));
    }

    public function test_admin_password_reset_requires_telegram_otp(): void
    {
        $admin = Admin::create([
            'Username' => 'otpadmin',
            'Password' => Hash::make('OldPassword123!'),
            'Phone' => '061954512',
            'FirstName' => 'Otp',
            'LastName' => 'Admin',
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        // 1. Trying to reset without OTP must fail (Validation error 422)
        $resNoOtp = $this->postJson('/api/password/reset', [
            'username' => 'otpadmin',
            'phone' => '061954512',
            'password' => 'NewPassword123!',
        ]);
        $resNoOtp->assertStatus(422);

        // 2. Request OTP via verify-identity
        $resVerify = $this->postJson('/api/password/verify-identity', [
            'username' => 'otpadmin',
            'phone' => '061954512',
        ]);
        $resVerify->assertStatus(200);
        $resVerify->assertJsonStructure(['message', 'username', 'displayName']);

        // Check OTP was stored in cache
        $cached = \Illuminate\Support\Facades\Cache::get("admin_reset_otp_{$admin->AdminId}");
        $this->assertNotNull($cached);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', (string)$cached['otp']);

        $correctOtp = (string)$cached['otp'];

        // 3. Trying verify-otp with incorrect OTP must fail
        $resWrongVerifyOtp = $this->postJson('/api/password/verify-otp', [
            'username' => 'otpadmin',
            'phone' => '061954512',
            'otp' => '000000',
        ]);
        $resWrongVerifyOtp->assertStatus(422);

        // 4. Trying verify-otp with correct OTP succeeds
        $resCorrectVerifyOtp = $this->postJson('/api/password/verify-otp', [
            'username' => 'otpadmin',
            'phone' => '061954512',
            'otp' => $correctOtp,
        ]);
        $resCorrectVerifyOtp->assertStatus(200);
        $resCorrectVerifyOtp->assertJson(['valid' => true]);

        // 5. Trying with incorrect OTP on reset must fail
        $resWrongOtp = $this->postJson('/api/password/reset', [
            'username' => 'otpadmin',
            'phone' => '061954512',
            'otp' => '000000',
            'password' => 'NewPassword123!',
        ]);
        $resWrongOtp->assertStatus(422);

        // 6. Reset with correct OTP succeeds
        $resSuccess = $this->postJson('/api/password/reset', [
            'username' => 'otpadmin',
            'phone' => '061954512',
            'otp' => $correctOtp,
            'password' => 'NewPassword123!',
        ]);
        $resSuccess->assertStatus(200);
        $resSuccess->assertJson(['redirect' => '/login']);

        // OTP must be cleared from cache
        $this->assertNull(\Illuminate\Support\Facades\Cache::get("admin_reset_otp_{$admin->AdminId}"));

        // Admin password must be updated
        $admin->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $admin->Password));
    }

    public function test_check_identifier_returns_minimal_password_requirement(): void
    {
        // Check identifier should only return requiresPassword boolean, never role or raw user details
        $res = $this->postJson('/api/check-identifier', [
            'identifier' => 'admin'
        ]);
        $res->assertStatus(200);
        $res->assertJsonStructure(['requiresPassword']);
        $this->assertArrayNotHasKey('role', $res->json());
        $this->assertArrayNotHasKey('exists', $res->json());
    }

    public function test_login_account_lockout_after_five_failed_attempts(): void
    {
        $admin = Admin::create([
            'Username' => 'lockout_test_user',
            'Password' => Hash::make('CorrectPassword123!'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        \Illuminate\Support\Facades\Cache::flush();

        // 4 failed attempts should return 422
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/api/login', [
                'identifier' => 'lockout_test_user',
                'password' => 'WrongPassword!',
                'lang' => 'en'
            ]);
            $res->assertStatus(422);
        }

        // 5th failed attempt triggers lockout (429) (Finding #3)
        $res5 = $this->postJson('/api/login', [
            'identifier' => 'lockout_test_user',
            'password' => 'WrongPassword!',
            'lang' => 'en'
        ]);
        $res5->assertStatus(429);
        $this->assertStringContainsString('locked', strtolower($res5->json('message')));

        // 6th attempt while locked should still be 429 even with correct password
        $res6 = $this->postJson('/api/login', [
            'identifier' => 'lockout_test_user',
            'password' => 'CorrectPassword123!',
            'lang' => 'en'
        ]);
        $res6->assertStatus(429);
    }

    public function test_advanced_security_headers_and_hardened_csp_are_present(): void
    {
        $response = $this->get('/api/public-settings');

        // Headers from Finding #4
        $this->assertEquals('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
        $this->assertEquals('same-origin', $response->headers->get('Cross-Origin-Resource-Policy'));
        $this->assertEquals('credentialless', $response->headers->get('Cross-Origin-Embedder-Policy'));
        $this->assertEquals('none', $response->headers->get('X-Permitted-Cross-Domain-Policies'));

        // CSP from Finding #2: Nonce-based, no unsafe-inline in script-src, no wildcard https:
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotEmpty($csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[a-zA-Z0-9+\/]+={0,2}'/", $csp);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }

    public function test_validation_errors_return_human_readable_messages(): void
    {
        $response = $this->postJson('/api/password/reset', [
            'username' => '',
            'phone' => '',
            'otp' => '',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $message = $response->json('message');
        $this->assertNotEmpty($message);
        // Ensure raw key like "validation.required" is not displayed (Finding #5)
        $this->assertStringNotContainsString('validation.required', $message);
    }

    public function test_password_reset_requires_minimum_eight_characters(): void
    {
        $admin = Admin::create([
            'Username' => 'pwd_policy_admin',
            'Password' => Hash::make('OldPassword123!'),
            'Phone' => '012345678',
            'FirstName' => 'Policy',
            'LastName' => 'Admin',
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        \Illuminate\Support\Facades\Cache::put("admin_reset_otp_{$admin->AdminId}", [
            'otp' => '123456',
            'attempts' => 0,
            'verified' => true,
        ], now()->addMinutes(10));

        // Attempt with 6-char password should fail (Finding #5 in README-Att.md)
        $res6 = $this->postJson('/api/password/reset', [
            'username' => 'pwd_policy_admin',
            'phone' => '012345678',
            'otp' => '123456',
            'password' => 'Pass1!',
        ]);
        $res6->assertStatus(422);

        // Attempt with 8+ chars should succeed
        $res8 = $this->postJson('/api/password/reset', [
            'username' => 'pwd_policy_admin',
            'phone' => '012345678',
            'otp' => '123456',
            'password' => 'Pass1234!',
        ]);
        $res8->assertStatus(200);
    }

    public function test_verify_identity_and_otp_do_not_enumerate_users(): void
    {
        Admin::create([
            'Username' => 'existing_admin_enum',
            'Password' => Hash::make('Secret123!'),
            'Phone' => '012345678',
            'FirstName' => 'Exist',
            'LastName' => 'Admin',
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        // Non-existent user
        $resNonExistent = $this->postJson('/api/password/verify-otp', [
            'username' => 'non_existent_admin_xyz',
            'phone' => '012345678',
            'otp' => '123456',
        ]);
        $resNonExistent->assertStatus(422);

        // Existing user with wrong phone
        $resWrongPhone = $this->postJson('/api/password/verify-otp', [
            'username' => 'existing_admin_enum',
            'phone' => '099999999',
            'otp' => '123456',
        ]);
        $resWrongPhone->assertStatus(422);

        // Both error messages must be strictly identical (Anti-Enumeration Finding #4)
        $this->assertEquals($resNonExistent->json('message'), $resWrongPhone->json('message'));
    }

    public function test_admin_and_student_with_same_id_update_independently(): void
    {
        // 1. Create a SuperAdmin to act as authenticated user
        $superAdmin = Admin::create([
            'Username' => 'superroot',
            'Password' => Hash::make('SuperSecret123!'),
            'FirstName' => 'Super',
            'LastName' => 'Admin',
            'Role' => 'SuperAdmin',
            'Status' => 'Active',
        ]);

        // 2. Create Skill and Group for student
        $skill = Skill::create(['SkillName' => 'Web Dev']);
        $group = Group::create(['GroupName' => 'Class A']);

        // 3. Create Student (will get StudentId = 1)
        $student = Student::create([
            'StudentCode' => 'RTC-2026-00001',
            'FirstName' => 'Sok',
            'LastName' => 'San',
            'Gender' => 'M',
            'StudyShift' => 'Morning',
            'SkillId' => $skill->SkillId,
            'GroupId' => $group->GroupId,
            'Phone' => '011111111',
        ]);

        // 4. Create Standard Admin (will get AdminId = 2)
        $targetAdmin = Admin::create([
            'Username' => 'adminmanager',
            'Password' => Hash::make('AdminPass123!'),
            'FirstName' => 'Admin',
            'LastName' => 'Manager',
            'Role' => 'Admin',
            'Status' => 'Active',
            'Phone' => '022222222',
        ]);

        // 5. Create a 2nd Student (will get StudentId = 2)
        $student2 = Student::create([
            'StudentCode' => 'RTC-2026-00002',
            'FirstName' => 'Dara',
            'LastName' => 'StudentTwo',
            'Gender' => 'F',
            'StudyShift' => 'Afternoon',
            'SkillId' => $skill->SkillId,
            'GroupId' => $group->GroupId,
            'Phone' => '033333333',
        ]);

        // Ensure both targetAdmin and student2 share ID = 2
        $this->assertEquals($targetAdmin->AdminId, $student2->StudentId);

        // 6. Act as SuperAdmin and update Admin #2
        $this->actingAs($superAdmin);

        $res = $this->putJson("/api/admin/admins/{$targetAdmin->AdminId}", [
            'firstName' => 'AdminUpdated',
            'lastName' => 'ManagerUpdated',
            'username' => 'admindom',
            'phone' => '061954512',
            'role' => 'Admin',
        ]);

        $res->assertStatus(200);
        $res->assertJson(['message' => 'Administrator updated successfully!']);

        // Verify Admin record was updated
        $targetAdmin->refresh();
        $this->assertEquals('admindom', $targetAdmin->Username);
        $this->assertEquals('AdminUpdated', $targetAdmin->FirstName);
        $this->assertEquals('061954512', $targetAdmin->Phone);

        // Verify Student with same ID was NOT touched
        $student2->refresh();
        $this->assertEquals('Dara', $student2->FirstName);
        $this->assertEquals('StudentTwo', $student2->LastName);
        $this->assertEquals('033333333', $student2->Phone);

        // 7. Also test backward-compatibility via PUT /api/admin/students/{id} with admin payload
        $resLegacy = $this->putJson("/api/admin/students/{$targetAdmin->AdminId}", [
            'firstName' => 'AdminDomFinal',
            'lastName' => 'ManagerFinal',
            'username' => 'admindomfinal',
            'phone' => '061954512',
            'role' => 'Admin',
        ]);

        $resLegacy->assertStatus(200);
        $resLegacy->assertJson(['message' => 'Administrator updated successfully!']);

        $targetAdmin->refresh();
        $this->assertEquals('admindomfinal', $targetAdmin->Username);
        $this->assertEquals('AdminDomFinal', $targetAdmin->FirstName);

        // Student #2 remains intact
        $student2->refresh();
        $this->assertEquals('Dara', $student2->FirstName);
    }

    public function test_public_settings_does_not_disclose_internal_configs(): void
    {
        // F-04: Ensure sessionTimeoutMinutes, maxExamAttempts, timezone are NOT disclosed to unauthenticated users
        $res = $this->getJson('/api/public-settings');
        $res->assertStatus(200);
        $settings = $res->json('settings');

        $this->assertIsArray($settings);
        $this->assertArrayNotHasKey('sessionTimeoutMinutes', $settings);
        $this->assertArrayNotHasKey('maxExamAttempts', $settings);
        $this->assertArrayNotHasKey('timezone', $settings);

        // Required UI values must still exist
        $this->assertArrayHasKey('institutionName', $settings);
        $this->assertArrayHasKey('academicYear', $settings);
        $this->assertArrayHasKey('defaultLanguage', $settings);
    }

    public function test_skills_groups_returns_generic_403_when_registration_disabled(): void
    {
        // F-05: When registration is disabled, skills-groups returns generic 'Access denied.' without internal config info
        \Illuminate\Support\Facades\Cache::forever('system_settings', ['allowRegistration' => false]);

        $res = $this->getJson('/api/skills-groups');
        $res->assertStatus(403);
        $this->assertEquals('Access denied.', $res->json('message'));
        $this->assertStringNotContainsString('registration', strtolower($res->json('message')));
    }

    public function test_login_enforces_captcha_after_three_failed_attempts(): void
    {
        Admin::create([
            'Username' => 'captcha_test_admin',
            'Password' => Hash::make('AdminSecret123!'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        \Illuminate\Support\Facades\Cache::flush();

        // 3 failed attempts
        for ($i = 1; $i <= 3; $i++) {
            $res = $this->postJson('/api/login', [
                'identifier' => 'captcha_test_admin',
                'password' => 'WrongPass!',
            ]);
            $res->assertStatus(422);
        }

        // 4th attempt without CAPTCHA must require CAPTCHA (requiresCaptcha: true)
        $res4 = $this->postJson('/api/login', [
            'identifier' => 'captcha_test_admin',
            'password' => 'WrongPass!',
        ]);
        $res4->assertStatus(422);
        $res4->assertJson(['requiresCaptcha' => true]);

        // Get CAPTCHA challenge
        $captchaRes = $this->getJson('/api/auth/captcha');
        $captchaRes->assertStatus(200);
        $captchaRes->assertJsonStructure(['token', 'question']);

        $token = $captchaRes->json('token');
        $question = $captchaRes->json('question');
        preg_match('/(\d+)\s*\+\s*(\d+)/', $question, $matches);
        $answer = (string)((int)$matches[1] + (int)$matches[2]);

        // Login with correct CAPTCHA and correct password succeeds
        $resSuccess = $this->postJson('/api/login', [
            'identifier' => 'captcha_test_admin',
            'password' => 'AdminSecret123!',
            'captcha_token' => $token,
            'captcha_answer' => $answer,
        ]);
        $resSuccess->assertStatus(200);
        $resSuccess->assertJson(['message' => 'Login successful.']);
    }

    public function test_account_is_locked_out_globally_across_multiple_ips(): void
    {
        Admin::create([
            'Username' => 'distributed_target',
            'Password' => Hash::make('StrongPass123!'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        \Illuminate\Support\Facades\Cache::flush();

        // Simulate 4 failed attempts from 4 different IPs
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->withServerVariables(['REMOTE_ADDR' => "198.51.100.{$i}"])
                ->postJson('/api/login', [
                    'identifier' => 'distributed_target',
                    'password' => 'WrongPass!',
                    'lang' => 'en',
                ]);
            $res->assertStatus(422);
        }

        // 5th attempt from a 5th distinct IP must lock the account globally (429)
        $res5 = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.5'])
            ->postJson('/api/login', [
                'identifier' => 'distributed_target',
                'password' => 'WrongPass!',
                'lang' => 'en',
            ]);
        $res5->assertStatus(429);
        $this->assertStringContainsString('locked', strtolower($res5->json('message')));

        // Even an attempt from yet another IP (6th IP) is now rejected with 429
        $res6 = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.6'])
            ->postJson('/api/login', [
                'identifier' => 'distributed_target',
                'password' => 'StrongPass123!',
                'lang' => 'en',
            ]);
        $res6->assertStatus(429);
    }

    public function test_parse_doc_rejects_disallowed_file_types(): void
    {
        $admin = Admin::create([
            'Username' => 'docadmin',
            'Password' => Hash::make('AdminPass123!'),
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);
        $this->actingAs($admin);

        // Upload an executable/PHP file masked as doc
        $fakePhp = \Illuminate\Http\UploadedFile::fake()->create('exploit.php', 100, 'application/x-php');

        $res = $this->postJson('/api/admin/tests/parse-doc', [
            'file' => $fakePhp,
        ]);
        $res->assertStatus(422);
    }

    public function test_otp_expires_and_locks_out_after_five_failed_attempts(): void
    {
        $admin = Admin::create([
            'Username' => 'otplockoutadmin',
            'Password' => Hash::make('AdminPass123!'),
            'Phone' => '077889900',
            'FirstName' => 'Otp',
            'LastName' => 'Lock',
            'Role' => 'Admin',
            'Status' => 'Active',
        ]);

        \Illuminate\Support\Facades\Cache::flush();

        // Request OTP
        $this->postJson('/api/password/verify-identity', [
            'username' => 'otplockoutadmin',
            'phone' => '077889900',
        ])->assertStatus(200);

        // 4 failed OTP attempts
        for ($i = 1; $i <= 4; $i++) {
            $res = $this->postJson('/api/password/verify-otp', [
                'username' => 'otplockoutadmin',
                'phone' => '077889900',
                'otp' => '000000',
            ]);
            $res->assertStatus(422);
        }

        // 5th failed OTP attempt must trigger lockout (429) and clear OTP from cache
        $res5 = $this->postJson('/api/password/verify-otp', [
            'username' => 'otplockoutadmin',
            'phone' => '077889900',
            'otp' => '000000',
        ]);
        $res5->assertStatus(429);

        // Cached OTP must be destroyed
        $this->assertNull(\Illuminate\Support\Facades\Cache::get("admin_reset_otp_{$admin->AdminId}"));

        // Subsequent OTP verify attempt is locked out
        $res6 = $this->postJson('/api/password/verify-otp', [
            'username' => 'otplockoutadmin',
            'phone' => '077889900',
            'otp' => '123456',
        ]);
        $res6->assertStatus(429);
    }
}

