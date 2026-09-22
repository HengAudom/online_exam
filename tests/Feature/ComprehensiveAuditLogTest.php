<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Skill;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComprehensiveAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $superAdmin;
    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = Admin::create([
            'Username' => 'superadmin_audit_test',
            'Password' => Hash::make('password123'),
            'Role' => 'Super Admin',
            'Status' => 'Active',
            'FirstName' => 'Super',
            'LastName' => 'Admin',
            'Phone' => '012999888',
        ]);

        $this->admin = Admin::create([
            'Username' => 'admin_audit_test',
            'Password' => Hash::make('password123'),
            'Role' => 'Admin',
            'Status' => 'Active',
            'FirstName' => 'Staff',
            'LastName' => 'Admin',
            'Phone' => '012777666',
        ]);
    }

    public function test_audit_logger_can_record_and_retrieve_logs(): void
    {
        AuditLogger::log(
            action: 'Manual Test Action',
            module: 'Exams',
            target: 'Sample Test Target',
            details: 'Detailed description for unit test',
            status: 'Success',
            user: $this->superAdmin
        );

        $this->assertDatabaseHas('tblauditlog', [
            'Action' => 'Manual Test Action',
            'Module' => 'Exams',
            'Target' => 'Sample Test Target',
            'UserName' => 'Super Admin',
            'UserRole' => 'Super Admin',
        ]);

        $logs = AuditLogger::getLogs();
        $found = $logs->firstWhere('action', 'Manual Test Action');
        $this->assertNotNull($found);
        $this->assertEquals('Exams', $found['module']);
        $this->assertEquals('Sample Test Target', $found['target']);
    }

    public function test_super_admin_can_retrieve_audit_logs_via_api(): void
    {
        AuditLogger::log(
            action: 'API View Verification',
            module: 'Authentication',
            target: '@testuser',
            details: 'Testing API retrieval',
            status: 'Success',
            user: $this->superAdmin
        );

        $response = $this->actingAs($this->superAdmin, 'web')
            ->getJson('/api/admin/audit-logs');

        $response->assertStatus(200)
            ->assertJsonStructure(['logs']);

        $logs = collect($response->json('logs'));
        $match = $logs->firstWhere('action', 'API View Verification');
        $this->assertNotNull($match);
    }

    public function test_super_admin_can_clear_audit_logs(): void
    {
        AuditLogger::log(
            action: 'To Be Cleared',
            module: 'Academic',
            target: 'Old Group',
            details: 'Will be deleted',
            status: 'Success',
            user: $this->superAdmin
        );

        $response = $this->actingAs($this->superAdmin, 'web')
            ->deleteJson('/api/admin/audit-logs');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // tblauditlog should be cleared except for the clear action itself
        $count = AuditLog::where('Action', 'To Be Cleared')->count();
        $this->assertEquals(0, $count);
    }

    public function test_academic_and_settings_operations_record_audit_logs(): void
    {
        // 1. Create a Skill
        $resSkill = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/api/admin/skills', [
                'name' => 'Fullstack Web Development'
            ]);
        $resSkill->assertStatus(201);

        $this->assertDatabaseHas('tblauditlog', [
            'Action' => 'Created Skill',
            'Module' => 'Academic',
            'Target' => 'Fullstack Web Development'
        ]);

        // 2. Create a Duration
        $resDur = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/api/admin/durations', [
                'name' => '6 Months Intensive',
                'months' => 6
            ]);
        $resDur->assertStatus(201);

        $this->assertDatabaseHas('tblauditlog', [
            'Action' => 'Created Duration',
            'Module' => 'Academic',
            'Target' => '6 Months Intensive'
        ]);

        // 3. Save System Settings
        $resSettings = $this->actingAs($this->superAdmin, 'web')
            ->postJson('/api/admin/system-settings', [
                'institutionName' => 'Updated Academic Center'
            ]);
        $resSettings->assertStatus(200);

        $this->assertDatabaseHas('tblauditlog', [
            'Action' => 'Updated System Settings',
            'Module' => 'System Settings'
        ]);
    }
}
