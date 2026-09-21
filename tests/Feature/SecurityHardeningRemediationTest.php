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

}
