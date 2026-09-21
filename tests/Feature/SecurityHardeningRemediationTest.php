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
}

