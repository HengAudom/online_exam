<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Skill;
use App\Models\Duration;
use App\Models\Student;
use App\Models\Admin;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    /**
     * Check permission against storage/app/permissions.json for Admin role.
     * Super Admin always has full unrestricted access.
     */
    public static function checkAdminPermission($user, string $module, string $action = 'view'): bool
    {
        if (!$user) return false;
        if (in_array($user->role ?? $user->Role, ['Super Admin', 'SuperAdmin'])) {
            return true;
        }
        if (($user->role ?? $user->Role) !== 'Admin') {
            return false;
        }

        $permsFile = storage_path('app/permissions.json');
        if (!file_exists($permsFile)) {
            return true;
        }

        $matrix = json_decode(file_get_contents($permsFile), true) ?: [];
        foreach ($matrix as $item) {
            $mName = trim($item['module'] ?? '');
            if (strcasecmp($mName, trim($module)) === 0) {
                if (isset($item[$action])) {
                    return (bool) $item[$action];
                }
            }
        }
        return true;
    }

    public function dashboard(Request $request)
    {
        $payload = Cache::remember('admin_dashboard_payload', 30, function () {
            $studentCount = Student::count();
            $adminCount = Admin::whereIn('Role', ['Admin', 'Super Admin', 'SuperAdmin'])->count();
            $totalUsers = $studentCount + $adminCount;
            $testCount = DB::table('tbltest')->count();
            $completedCount = DB::table('tblstudentsubmission')->whereNotNull('CompletedAt')->count();
            $avgScore = DB::table('tblstudentsubmission')
                ->whereNotNull('CompletedAt')
                ->avg('Score') ?? 0;

        $submissions = DB::table('tblstudentsubmission as ss')
            ->leftJoin('tblstudent as s', 'ss.StudentId', '=', 's.StudentId')
            ->leftJoin('tbltest as t', 'ss.TestId', '=', 't.TestId')
            ->orderBy('ss.SubmissionId', 'desc')
            ->limit(20)
            ->select('s.FirstName', 's.LastName', 't.TestName', 'ss.Score', 'ss.CompletedAt as Date', 'ss.SubmissionId')
            ->get()
            ->map(function($r) {
                $studentName = trim(($r->FirstName ?? '') . ' ' . ($r->LastName ?? ''));
                if (!$studentName) $studentName = 'Student #' . $r->SubmissionId;
                $testName = $r->TestName ?? 'Exam';
                $time = !empty($r->Date) ? \Carbon\Carbon::parse($r->Date)->timestamp : (1700000000 + ($r->SubmissionId * 10));
                $dateFormatted = !empty($r->Date) ? date('M d, Y', strtotime($r->Date)) : 'Recent';
                return [
                    'id' => 'sub_' . $r->SubmissionId,
                    'type' => 'exam_completion',
                    'module' => 'Exams',
                    'action' => 'Exam Completed',
                    'user' => $studentName,
                    'title' => "{$studentName} completed {$testName}",
                    'description' => "Score: {$r->Score} · {$dateFormatted}",
                    'timestamp' => $time,
                    'status' => 'Success'
                ];
            });

        $newTests = DB::table('tbltest')
            ->orderBy('TestId', 'desc')
            ->limit(10)
            ->select('TestName', 'created_at as Date', 'TestId')
            ->get()
            ->map(function($r) {
                $time = !empty($r->Date) ? \Carbon\Carbon::parse($r->Date)->timestamp : (1700000000 + ($r->TestId * 50));
                $dateFormatted = !empty($r->Date) ? date('M d, Y', strtotime($r->Date)) : 'Active';
                return [
                    'id' => 'test_' . $r->TestId,
                    'type' => 'new_test',
                    'module' => 'Exams',
                    'action' => 'Test Published',
                    'user' => 'Admin',
                    'title' => "New exam published: {$r->TestName}",
                    'description' => "Test ID #{$r->TestId} · {$dateFormatted}",
                    'timestamp' => $time,
                    'status' => 'Success'
                ];
            });

        $newStudents = DB::table('tblstudent as s')
            ->leftJoin('tblskill as sk', 's.SkillId', '=', 'sk.SkillId')
            ->leftJoin('tblgroup as g', 's.GroupId', '=', 'g.GroupId')
            ->orderBy('s.StudentId', 'desc')
            ->limit(10)
            ->select('s.FirstName', 's.LastName', 'sk.SkillName', 'g.GroupName', 's.created_at as Date', 's.StudentId')
            ->get()
            ->map(function($r) {
                $studentName = trim(($r->FirstName ?? '') . ' ' . ($r->LastName ?? ''));
                if (!$studentName) $studentName = 'Student #' . $r->StudentId;
                $skill = $r->SkillName ?? 'General';
                $group = $r->GroupName ?? 'Cohort';
                $time = !empty($r->Date) ? \Carbon\Carbon::parse($r->Date)->timestamp : (1700000000 + ($r->StudentId * 20));
                return [
                    'id' => 'stu_' . $r->StudentId,
                    'type' => 'new_student',
                    'module' => 'Students',
                    'action' => 'Student Enrolled',
                    'user' => $studentName,
                    'title' => "New student registered: {$studentName}",
                    'description' => "Skill: {$skill} · Group: {$group}",
                    'timestamp' => $time,
                    'status' => 'Success'
                ];
            });

        $adminTable = Schema::hasTable('tbladmin') ? 'tbladmin' : 'tbladminprofile';
        $adminIdCol = Schema::hasColumn($adminTable, 'AdminId') ? 'AdminId' : 'AdminProfileId';
        $newAdmins = DB::table($adminTable)
            ->orderBy($adminIdCol, 'desc')
            ->limit(10)
            ->get()
            ->map(function($r) use ($adminIdCol) {
                $time = !empty($r->created_at) ? \Carbon\Carbon::parse($r->created_at)->timestamp : time();
                $id = $r->{$adminIdCol};
                $name = trim("{$r->FirstName} {$r->LastName}") ?: $r->Username;
                return [
                    'id' => 'adm_' . $id,
                    'type' => 'new_admin',
                    'module' => 'Admins',
                    'action' => 'Admin Account Added',
                    'user' => $name,
                    'title' => "New Admin added: {$name}",
                    'description' => "Username: @{$r->Username}",
                    'timestamp' => $time,
                    'status' => 'Success'
                ];
            });

        $newGroups = DB::table('tblgroup')
            ->orderBy('GroupId', 'desc')
            ->limit(10)
            ->get()
            ->map(function($r) {
                $time = !empty($r->created_at) ? \Carbon\Carbon::parse($r->created_at)->timestamp : (1700000000 + ($r->GroupId * 5));
                return [
                    'id' => 'grp_' . $r->GroupId,
                    'type' => 'new_group',
                    'module' => 'Academic',
                    'action' => 'Group Created',
                    'user' => 'Admin',
                    'title' => "New group added: {$r->GroupName}",
                    'description' => "Cohort {$r->GroupName}",
                    'timestamp' => $time,
                    'status' => 'Success'
                ];
            });

        $newSkills = DB::table('tblskill')
            ->orderBy('SkillId', 'desc')
            ->limit(10)
            ->get()
            ->map(function($r) {
                $time = !empty($r->created_at) ? \Carbon\Carbon::parse($r->created_at)->timestamp : (1700000000 + ($r->SkillId * 5));
                return [
                    'id' => 'skl_' . $r->SkillId,
                    'type' => 'new_skill',
                    'module' => 'Academic',
                    'action' => 'Skill Created',
                    'user' => 'Admin',
                    'title' => "New skill area added: {$r->SkillName}",
                    'description' => "Department: {$r->SkillName}",
                    'timestamp' => $time,
                    'status' => 'Success'
                ];
            });

        $latestActivity = collect()
            ->concat($submissions)
            ->concat($newTests)
            ->concat($newStudents)
            ->concat($newAdmins)
            ->concat($newGroups)
            ->concat($newSkills)
            ->sort(function ($a, $b) {
                if ($a['timestamp'] === $b['timestamp']) {
                    return strcmp($b['id'], $a['id']);
                }
                return $b['timestamp'] <=> $a['timestamp'];
            })
            ->take(30)
            ->values();

        $dbStats = self::getRealDatabaseStorageStats();
        $dbSizeMB = $dbStats['usedMB'];
        $tableCount = $dbStats['tableCount'];
        $driverLabel = $dbStats['driver'];
        $dbName = $dbStats['dbName'];

        // Configurable Hosting / Database Quota (Default 5120 MB / 5 GB TiDB Cloud Serverless Free Tier)
        $settings = self::getSystemSettings();
        $envQuota = (int) ($settings['dbStorageQuotaMB'] ?? env('DB_STORAGE_LIMIT_MB', env('HOSTING_STORAGE_LIMIT_MB', 5120)));
        $totalMB = $envQuota > 0 ? $envQuota : 5120;
        $remainingMB = max(0, round($totalMB - $dbSizeMB, 2));
        $percentage = $totalMB > 0 ? round(($dbSizeMB / $totalMB) * 100, 2) : 0;

        return [
            'totalUsers' => $totalUsers,
            'totalAdmins' => $adminCount,
            'activeStudents' => $studentCount,
            'publishedTests' => $testCount,
            'completedExams' => $completedCount,
            'avgScore' => round($avgScore, 1),
            'latestActivity' => $latestActivity,
            'systemHealth' => self::runSystemHealthCheck(),
            'databaseStorage' => [
                'used' => $dbSizeMB,
                'remaining' => $remainingMB,
                'total' => $totalMB,
                'percentage' => $percentage,
                'driver' => $driverLabel,
                'databaseName' => $dbName,
                'tableCount' => $tableCount,
                'bytes' => $dbStats['bytes'] ?? 0,
            ]
        ];
    });

    return response()->json($payload);
}

    public function auditLogs(Request $request)
    {
        $clearedAt = null;
        $metaFile = storage_path('app/audit_logs_meta.json');
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
            $clearedAt = $meta['cleared_at'] ?? null;
        }

        $loginLogs = collect();

        try {
            $jsonFile = storage_path('app/login_logs.json');
            if (file_exists($jsonFile)) {
                $fileLogs = json_decode(file_get_contents($jsonFile), true) ?: [];
                $loginLogs = collect($fileLogs)->map(fn($l) => [
                    'id' => $l['id'] ?? ('auth-json-' . uniqid()),
                    'date' => $l['date'] ?? now()->toDateTimeString(),
                    'user' => $l['displayName'] ?: ($l['username'] ?? 'Unknown'),
                    'role' => $l['role'] ?? 'User',
                    'action' => ($l['status'] ?? '') === 'Failed' ? 'Failed Login Attempt' : (($l['status'] ?? '') === 'Logged Out' ? 'User Logout' : 'User Login'),
                    'module' => 'Authentication',
                    'target' => '@' . ($l['username'] ?? 'unknown'),
                    'status' => ($l['status'] ?? '') === 'Failed' ? 'Failed' : (($l['status'] ?? '') === 'Logged Out' ? 'Logged Out' : 'Success'),
                    'details' => $l['details'] ?? "IP: " . ($l['ipAddress'] ?? '127.0.0.1')
                ]);
            }
        } catch (\Throwable $e) {
            \Log::warning('Error reading login_logs.json: ' . $e->getMessage());
        }

        if ($clearedAt) {
            $loginLogs = $loginLogs->filter(fn($l) => isset($l['date']) && $l['date'] > $clearedAt);
        }

        $uniqueLoginLogs = $loginLogs->take(80);

        $subQuery = DB::table('tblstudentsubmission as ss')
            ->join('tblstudent as s', 'ss.StudentId', '=', 's.StudentId')
            ->join('tbltest as t', 'ss.TestId', '=', 't.TestId')
            ->whereNotNull('ss.CompletedAt');

        if ($clearedAt) {
            $subQuery->where('ss.CompletedAt', '>', $clearedAt);
        }

        $submissions = $subQuery
            ->orderBy('ss.CompletedAt', 'desc')
            ->limit(50)
            ->select('ss.SubmissionId', 's.FirstName', 's.LastName', 't.TestName', 'ss.Score', 'ss.CompletedAt as Date')
            ->get()
            ->map(fn($r) => [
                'id' => 'sub-' . $r->SubmissionId,
                'date' => $r->Date,
                'user' => "{$r->FirstName} {$r->LastName}",
                'role' => 'Student',
                'action' => 'Exam Submission',
                'module' => 'Exams',
                'target' => $r->TestName,
                'status' => 'Completed',
                'details' => "Score: {$r->Score} points"
            ]);

        $adminNameSql = DB::connection()->getDriverName() === 'sqlite'
            ? "(a.FirstName || ' ' || a.LastName)"
            : "CONCAT(COALESCE(a.FirstName, ''), ' ', COALESCE(a.LastName, ''))";

        $testQuery = DB::table('tbltest as t')
            ->leftJoin('tbladmin as a', 't.CreatedByUserId', '=', 'a.AdminId');

        if ($clearedAt) {
            $testQuery->where('t.created_at', '>', $clearedAt);
        }

        $tests = $testQuery
            ->orderBy('t.created_at', 'desc')
            ->limit(50)
            ->select('t.TestId', 't.TestName', DB::raw("COALESCE(NULLIF(TRIM($adminNameSql), ''), a.Username, 'Admin') as userName"), 't.created_at as Date', 't.Status')
            ->get()
            ->map(fn($r) => [
                'id' => 'test-' . $r->TestId,
                'date' => $r->Date,
                'user' => $r->userName,
                'role' => 'Admin',
                'action' => 'Exam Published / Drafted',
                'module' => 'Exams',
                'target' => $r->TestName,
                'status' => $r->Status,
                'details' => "Exam status set to {$r->Status}"
            ]);

        $stuQuery = DB::table('tblstudent as s')
            ->leftJoin('tblskill as sk', 's.SkillId', '=', 'sk.SkillId');

        if ($clearedAt) {
            $stuQuery->where('s.created_at', '>', $clearedAt);
        }

        $students = $stuQuery
            ->orderBy('s.created_at', 'desc')
            ->limit(50)
            ->select('s.StudentId', 's.FirstName', 's.LastName', 's.StudentCode', 'sk.SkillName', 's.created_at as Date')
            ->get()
            ->map(fn($r) => [
                'id' => 'stu-' . $r->StudentId,
                'date' => $r->Date,
                'user' => trim("{$r->FirstName} {$r->LastName}") ?: ($r->StudentCode ?? ('Student #' . $r->StudentId)),
                'role' => 'Student',
                'action' => 'Account Registration',
                'module' => 'Students',
                'target' => $r->StudentCode ? "@{$r->StudentCode}" : "@student{$r->StudentId}",
                'status' => 'Active',
                'details' => "Enrolled in " . ($r->SkillName ?? 'General')
            ]);

        $adminTable = Schema::hasTable('tbladmin') ? 'tbladmin' : 'tbladminprofile';
        $adminIdCol = Schema::hasColumn($adminTable, 'AdminId') ? 'AdminId' : 'AdminProfileId';
        $admQuery = DB::table($adminTable);

        if ($clearedAt) {
            $admQuery->where('created_at', '>', $clearedAt);
        }

        $admins = $admQuery
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function($r) use ($adminIdCol) {
                $name = trim("{$r->FirstName} {$r->LastName}") ?: $r->Username;
                return [
                    'id' => 'adm-' . $r->{$adminIdCol},
                    'date' => $r->created_at,
                    'user' => $name,
                    'role' => $r->Role ?? 'Admin',
                    'action' => 'Admin Creation',
                    'module' => 'User Management',
                    'target' => "@{$r->Username}",
                    'status' => $r->Status ?? 'Active',
                    'details' => "Role: " . ($r->Role ?? 'Admin')
                ];
            });

        $logs = collect()
            ->concat($uniqueLoginLogs)
            ->concat($submissions)
            ->concat($tests)
            ->concat($students)
            ->concat($admins)
            ->sortByDesc('date')
            ->values();

        return response()->json(['logs' => $logs]);
    }

    public function clearAuditLogs(Request $request)
    {
        try {
            $jsonFile = storage_path('app/login_logs.json');
            if (file_exists($jsonFile)) {
                file_put_contents($jsonFile, json_encode([]));
            }

            $activityFile = storage_path('app/activity_logs.json');
            if (file_exists($activityFile)) {
                file_put_contents($activityFile, json_encode([]));
            }

            // Find highest timestamp among current logs to guarantee everything existing is purged
            $maxSub = DB::table('tblstudentsubmission')->max('CompletedAt');
            $maxTest = DB::table('tbltest')->max('created_at');
            $maxStu = DB::table('tblstudent')->max('created_at');
            $maxAdm = DB::table('tbladminprofile')->max('created_at');

            $dates = array_filter([$maxSub, $maxTest, $maxStu, $maxAdm, now()->toDateTimeString()]);
            rsort($dates);
            $highWaterMark = $dates[0] ?? now()->toDateTimeString();

            $metaFile = storage_path('app/audit_logs_meta.json');
            file_put_contents($metaFile, json_encode([
                'cleared_at' => $highWaterMark,
                'cleared_by' => auth()->user()?->name ?? 'Super Admin'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'All audit logs cleared successfully.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear audit logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rolesPermissions(Request $request)
    {
        $defaultMatrix = [
            ['module' => 'Students', 'view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => true],
            ['module' => 'Exams', 'view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => true],
            ['module' => 'Question Bank', 'view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => true],
            ['module' => 'Skills & Groups', 'view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => false],
            ['module' => 'Results', 'view' => true, 'create' => false, 'edit' => false, 'delete' => true, 'export' => true],
            ['module' => 'Analytics', 'view' => true, 'create' => false, 'edit' => false, 'delete' => false, 'export' => true],
            ['module' => 'Audit Logs', 'view' => true, 'create' => false, 'edit' => false, 'delete' => false, 'export' => true],
            ['module' => 'System Settings', 'view' => true, 'create' => false, 'edit' => true, 'delete' => false, 'export' => false],
        ];

        $permsFile = storage_path('app/permissions.json');
        $matrix = $defaultMatrix;
        if (file_exists($permsFile)) {
            $saved = json_decode(file_get_contents($permsFile), true);
            if (is_array($saved) && !empty($saved)) {
                $matrix = $saved;
            }
        }

        return response()->json([
            'roles' => [
                ['name' => 'Super Admin', 'description' => 'Full administrative access to all system components, roles, and settings', 'usersCount' => Admin::whereIn('Role', ['Super Admin', 'SuperAdmin'])->count()],
                ['name' => 'Admin', 'description' => 'Academic and student operations, exam builder, and result evaluation', 'usersCount' => Admin::where('Role', 'Admin')->count()],
                ['name' => 'Student', 'description' => 'Examinee access to take exams and review individual scores', 'usersCount' => Student::count()]
            ],
            'permissions' => $matrix
        ]);
    }

    public function saveRolesPermissions(Request $request)
    {
        $permissions = $request->input('permissions', []);
        if (!empty($permissions)) {
            $dir = storage_path('app');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents(storage_path('app/permissions.json'), json_encode($permissions, JSON_PRETTY_PRINT));
        }

        return response()->json(['message' => 'Roles & permissions matrix updated successfully!']);
    }

    public static function getSystemSettings(): array
    {
        $defaults = [
            'institutionName' => 'OnlineXam',
            'academicYear' => '2026-2027',
            'timezone' => 'Asia/Phnom_Penh',
            'defaultLanguage' => 'kh',
            'sessionTimeoutMinutes' => 60,
            'allowRegistration' => true,
            'forceStrongPassword' => true,
            'antiCheatPause' => true,
            'autosaveIntervalSeconds' => 3,
            'autoSubmitOnTimeout' => true,
            'maxExamAttempts' => 1,
            'phpVersion' => phpversion(),
            'laravelVersion' => app()->version(),
            'databaseDriver' => config('database.default'),
            'serverTime' => now()->toDateTimeString()
        ];

        $settingsFile = storage_path('app/settings.json');
        if (file_exists($settingsFile)) {
            $saved = json_decode(file_get_contents($settingsFile), true);
            if (is_array($saved)) {
                $defaults = array_merge($defaults, $saved);
            }
        }

        $defaults['phpVersion'] = phpversion();
        $defaults['laravelVersion'] = app()->version();
        $defaults['databaseDriver'] = config('database.default');
        $defaults['serverTime'] = now()->toDateTimeString();

        return $defaults;
    }

    public function publicSettings()
    {
        $settings = self::getSystemSettings();
        return response()->json(['settings' => $settings]);
    }

    public function systemSettings(Request $request)
    {
        $settings = self::getSystemSettings();
        return response()->json(['settings' => $settings]);
    }

    public function saveSystemSettings(Request $request)
    {
        if (!self::checkAdminPermission($request->user(), 'System Settings', 'edit')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to modify system settings.'], 403);
        }

        $data = $request->validate([
            'institutionName' => 'nullable|string|max:255',
            'academicYear' => 'nullable|string|max:100',
            'defaultLanguage' => 'nullable|string|in:kh,en',
            'sessionTimeoutMinutes' => 'nullable|integer|min:5|max:1440',
            'allowRegistration' => 'nullable|boolean',
            'forceStrongPassword' => 'nullable|boolean',
            'antiCheatPause' => 'nullable|boolean',
            'autosaveIntervalSeconds' => 'nullable|integer|min:1|max:60',
            'autoSubmitOnTimeout' => 'nullable|boolean',
            'maxExamAttempts' => 'nullable|integer|min:1',
        ]);

        $dir = storage_path('app');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $settingsFile = storage_path('app/settings.json');
        $existing = file_exists($settingsFile) ? (json_decode(file_get_contents($settingsFile), true) ?: []) : [];
        $merged = array_merge($existing, $data);
        file_put_contents($settingsFile, json_encode($merged, JSON_PRETTY_PRINT));

        return response()->json(['message' => 'System settings updated successfully!', 'settings' => self::getSystemSettings()]);
    }

    public function students(Request $request)
    {
        try {
            $submissionCounts = [];
            $studentSubmissions = [];
            try {
                $submissionCounts = DB::table('tblstudentsubmission as ss')
                    ->whereNotNull('ss.CompletedAt')
                    ->groupBy('ss.StudentId')
                    ->select('ss.StudentId', DB::raw('COUNT(ss.SubmissionId) as count'))
                    ->pluck('count', 'ss.StudentId')
                    ->toArray();

                $studentSubmissions = DB::table('tblstudentsubmission as ss')
                    ->join('tbltest as t', 'ss.TestId', '=', 't.TestId')
                    ->select('ss.StudentId', 't.TestId', 't.TestName', 'ss.Score', 'ss.CompletedAt')
                    ->get()
                    ->groupBy('StudentId');
            } catch (\Throwable $e) {
                \Log::warning('Failed to calculate submission counts: ' . $e->getMessage());
            }

            $durations = collect();
            try {
                $durations = Duration::all();
            } catch (\Throwable $e) {
                $durations = collect();
            }

            // 1. Query Students strictly from tblstudent
            $students = DB::table('tblstudent as s')
                ->leftJoin('tblskill as sk', 's.SkillId', '=', 'sk.SkillId')
                ->leftJoin('tblgroup as b', 's.GroupId', '=', 'b.GroupId')
                ->select(
                    's.StudentId as id',
                    's.StudentId as studentId',
                    's.StudentCode as studentCode',
                    's.FirstName as firstName',
                    's.LastName as lastName',
                    's.Phone as phone',
                    's.Photo as photo',
                    's.Photo as profileImage',
                    's.Gender as gender',
                    's.StudyShift as shift',
                    's.EnrolledMonth as enrolledMonth',
                    's.EnrolledYear as enrolledYear',
                    's.DurationMonths as durationMonths',
                    'sk.SkillName as skill',
                    'b.GroupName as group',
                    's.created_at as createdAt'
                )
                ->orderBy('s.StudentId', 'desc')
                ->get()
                ->map(function ($s) use ($submissionCounts, $studentSubmissions, $durations) {
                    $examCount = $submissionCounts[$s->id] ?? 0;
                    $durationObj = $durations->firstWhere('DurationMonths', $s->durationMonths);
                    $durationName = $durationObj ? $durationObj->DurationName : ($s->durationMonths ? ($s->durationMonths >= 12 ? '1 ឆ្នាំ (1 Year)' : "{$s->durationMonths} ខែ ({$s->durationMonths} Months)") : '1 ខែ (1 Month)');

                    $taken = $studentSubmissions[$s->id] ?? collect();
                    $takenExams = $taken->map(fn($t) => [
                        'testId' => $t->TestId,
                        'testName' => $t->TestName,
                        'score' => $t->Score,
                        'completedAt' => $t->CompletedAt,
                    ])->values()->all();
                    $takenExamNames = $taken->pluck('TestName')->filter()->unique()->values()->all();

                    $fullName = trim(($s->firstName ?? '') . ' ' . ($s->lastName ?? ''));
                    $displayCode = $s->studentCode ?: ('RTC-2026-' . str_pad((string)$s->id, 5, '0', STR_PAD_LEFT));

                    $photoUrl = null;
                    if (!empty($s->photo)) {
                        if (str_starts_with($s->photo, '/uploads/') || str_starts_with($s->photo, 'http://') || str_starts_with($s->photo, 'https://')) {
                            $photoUrl = $s->photo;
                        } else {
                            $photoUrl = '/api/student-photo/' . $s->id;
                        }
                    }

                    return [
                        'id' => $s->id,
                        'studentId' => $s->id,
                        'studentCode' => $displayCode,
                        'name' => $fullName ?: $displayCode,
                        'first_name' => $s->firstName,
                        'last_name' => $s->lastName,
                        'firstName' => $s->firstName,
                        'lastName' => $s->lastName,
                        'email' => $displayCode,
                        'username' => $displayCode,
                        'phone' => $s->phone,
                        'photo' => $photoUrl,
                        'profileImage' => $photoUrl,
                        'role' => 'Student',
                        'status' => 'Active',
                        'gender' => $s->gender,
                        'shift' => $s->shift,
                        'enrolledMonth' => $s->enrolledMonth,
                        'enrolledYear' => $s->enrolledYear,
                        'durationMonths' => $durationName,
                        'durationMonthsRaw' => $s->durationMonths ?? 1,
                        'skill' => $s->skill,
                        'group' => $s->group,
                        'hasTakenExam' => $examCount > 0,
                        'examCount' => $examCount,
                        'takenExams' => $takenExams,
                        'takenExamNames' => $takenExamNames,
                    ];
                })
                ->values();

            // 2. Query Admins strictly from tbladmin (fallback tbladminprofile)
            $adminTable = Schema::hasTable('tbladmin') ? 'tbladmin' : 'tbladminprofile';
            $adminIdCol = Schema::hasColumn($adminTable, 'AdminId') ? 'AdminId' : 'AdminProfileId';

            $admins = DB::table($adminTable)
                ->orderBy($adminIdCol, 'desc')
                ->get()
                ->map(function ($a) use ($adminIdCol) {
                    $fullName = trim(($a->FirstName ?? '') . ' ' . ($a->LastName ?? ''));
                    $photoUrl = null;
                    if (!empty($a->ProfileImage)) {
                        if (str_starts_with($a->ProfileImage, '/uploads/') || str_starts_with($a->ProfileImage, 'http://') || str_starts_with($a->ProfileImage, 'https://')) {
                            $photoUrl = $a->ProfileImage;
                        } else {
                            $photoUrl = '/api/admin-photo/' . $a->{$adminIdCol};
                        }
                    }

                    return [
                        'id' => $a->{$adminIdCol},
                        'name' => $fullName ?: $a->Username,
                        'first_name' => $a->FirstName,
                        'last_name' => $a->LastName,
                        'firstName' => $a->FirstName,
                        'lastName' => $a->LastName,
                        'email' => $a->Username,
                        'username' => $a->Username,
                        'phone' => $a->Phone,
                        'role' => $a->Role ?? 'Admin',
                        'status' => $a->Status ?? 'Active',
                        'photo' => $photoUrl,
                        'profileImage' => $photoUrl,
                    ];
                })
                ->values();

            $exams = collect();
            try {
                $exams = DB::table('tbltest')
                    ->orderBy('TestName')
                    ->select('TestId', 'TestName')
                    ->get();
            } catch (\Throwable $e) {
                $exams = collect();
            }

            return response()->json([
                'students' => $students,
                'admins' => $admins,
                'exams' => $exams,
            ]);
        } catch (\Throwable $e) {
            \Log::error('AdminController::students exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'មានបញ្ហាតភ្ជាប់មូលដ្ឋានទិន្នន័យ (Database connection error).',
                'students' => [],
                'admins' => [],
                'exams' => []
            ], 500);
        }
    }

    public function skillsGroups(Request $request)
    {
        $data = Cache::remember('admin_skills_groups', 60, function () {
            $skills = Skill::orderBy('SkillName')->get(['SkillId', 'SkillName', 'Description']);
            $groups = Group::orderBy('GroupName')->get(['GroupId', 'GroupName']);
            $durations = Duration::orderBy('DurationMonths')->get(['DurationId', 'DurationName', 'DurationMonths']);

            return [
                'skills' => $skills,
                'groups' => $groups,
                'durations' => $durations,
            ];
        });

        return response()->json($data);
    }

    public function addDuration(Request $request)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'create')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to create in Skills & Groups.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'months' => ['nullable', 'integer', 'min:1'],
        ]);
        $months = $data['months'] ?? (preg_match('/(\d+)/', $data['name'], $m) ? (int)$m[1] : 3);
        $duration = Duration::create([
            'DurationName' => $data['name'],
            'DurationMonths' => $months,
        ]);
        Cache::forget('admin_skills_groups');
        return response()->json(['duration' => $duration], 201);
    }

    public function deleteDuration(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'delete')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to delete in Skills & Groups.'], 403);
        }

        Duration::findOrFail($id)->delete();
        Cache::forget('admin_skills_groups');
        return response()->json(['message' => 'Duration deleted.']);
    }

    public function updateDuration(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'edit')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit in Skills & Groups.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'months' => ['nullable', 'integer', 'min:1'],
        ]);
        $duration = Duration::findOrFail($id);
        $months = $data['months'] ?? (preg_match('/(\d+)/', $data['name'], $m) ? (int)$m[1] : $duration->DurationMonths);
        $duration->update([
            'DurationName' => $data['name'],
            'DurationMonths' => $months,
        ]);
        Cache::forget('admin_skills_groups');
        return response()->json(['duration' => $duration]);
    }

    public function addSkill(Request $request)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'create')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to create in Skills & Groups.'], 403);
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $skill = Skill::create(['SkillName' => $data['name'], 'Description' => '']);
        Cache::forget('admin_skills_groups');
        return response()->json(['skill' => ['SkillId' => $skill->SkillId, 'SkillName' => $skill->SkillName]], 201);
    }

    public function deleteSkill(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'delete')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to delete in Skills & Groups.'], 403);
        }

        Skill::findOrFail($id)->delete();
        Cache::forget('admin_skills_groups');
        return response()->json(['message' => 'Skill deleted.']);
    }

    public function updateSkill(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'edit')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit in Skills & Groups.'], 403);
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $skill = Skill::findOrFail($id);
        $skill->update(['SkillName' => $data['name']]);
        return response()->json(['skill' => ['SkillId' => $skill->SkillId, 'SkillName' => $skill->SkillName]]);
    }

    public function addGroup(Request $request)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'create')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to create in Skills & Groups.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $group = Group::create([
            'GroupName' => $data['name'],
        ]);
        return response()->json([
            'group' => [
                'GroupId' => $group->GroupId,
                'GroupName' => $group->GroupName,
            ]
        ], 201);
    }

    public function deleteGroup(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'delete')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to delete in Skills & Groups.'], 403);
        }

        Group::findOrFail($id)->delete();
        return response()->json(['message' => 'Group deleted.']);
    }

    public function updateGroup(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Skills & Groups', 'edit')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit in Skills & Groups.'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $group = Group::findOrFail($id);
        $group->update([
            'GroupName' => $data['name'],
        ]);
        return response()->json([
            'group' => [
                'GroupId' => $group->GroupId,
                'GroupName' => $group->GroupName,
            ]
        ]);
    }

    public function tests(Request $request)
    {
        try {
            $adminNameSql = DB::connection()->getDriverName() === 'sqlite'
                ? "(a.FirstName || ' ' || a.LastName)"
                : "CONCAT(COALESCE(a.FirstName, ''), ' ', COALESCE(a.LastName, ''))";

            $tests = DB::table('tbltest as t')
                ->leftJoin('tblskill as sk', 't.SkillId', '=', 'sk.SkillId')
                ->leftJoin('tbladmin as a', 't.CreatedByUserId', '=', 'a.AdminId')
                ->leftJoin('tblgroup as b', 't.GroupId', '=', 'b.GroupId')
                ->select(
                    't.TestId as id',
                    't.TestName as name',
                    't.SkillId as skillId',
                    't.GroupId as groupId',
                    DB::raw("COALESCE(sk.SkillName, 'ទូទៅ (General)') as skill"),
                    DB::raw("COALESCE(b.GroupName, '') as `group`"),
                    't.DurationMinutes as durationMinutes',
                    't.TotalMarks as totalMarks',
                    't.Status as status',
                    't.ScheduledAt as scheduledAt',
                    't.FinishedAt as finishedAt',
                    't.created_at as createdAt',
                    DB::raw("COALESCE(NULLIF(TRIM($adminNameSql), ''), a.Username, 'Admin') as createdBy"),
                    DB::raw('(SELECT COUNT(*) FROM tblquestion WHERE tblquestion.TestId = t.TestId) as questionCount')
                )
                ->orderBy('t.TestId', 'desc')
                ->get()
                ->map(function ($t) {
                    $status = $t->status;
                    if ($status === 'Published') {
                        $end = null;
                        if ($t->finishedAt) {
                            $end = \Carbon\Carbon::parse($t->finishedAt);
                        } elseif ($t->scheduledAt) {
                            $end = \Carbon\Carbon::parse($t->scheduledAt)->addMinutes($t->durationMinutes);
                        }

                        if ($end && now()->greaterThan($end)) {
                            $status = 'Finished';
                        }
                    }
                    $t->status = $status;
                    return $t;
                });

            return response()->json(['tests' => $tests]);
        } catch (\Throwable $e) {
            \Log::error('AdminController::tests exception: ' . $e->getMessage());
            return response()->json([
                'message' => 'មានបញ្ហាតភ្ជាប់មូលដ្ឋានទិន្នន័យ (Database connection error).',
                'tests' => []
            ], 500);
        }
    }

    public function results(Request $request)
    {
        $nameSql = DB::connection()->getDriverName() === 'sqlite'
            ? "(s.FirstName || ' ' || s.LastName) as studentName"
            : "CONCAT(s.FirstName, ' ', s.LastName) as studentName";

        $results = DB::table('tblstudentsubmission as ss')
            ->join('tblstudent as s', 'ss.StudentId', '=', 's.StudentId')
            ->join('tbltest as t', 'ss.TestId', '=', 't.TestId')
            ->leftJoin('tblskill as sk', 's.SkillId', '=', 'sk.SkillId')
            ->leftJoin('tblgroup as b', 's.GroupId', '=', 'b.GroupId')
            ->whereNotNull('ss.CompletedAt')
            ->select(
                'ss.SubmissionId as id',
                's.StudentId as studentId',
                DB::raw($nameSql),
                't.TestId as testId',
                't.TestName as testName',
                't.TotalMarks as totalMarks',
                'ss.TotalCorrect as totalCorrect',
                'ss.Score as score',
                'ss.CompletedAt as completedAt',
                'sk.SkillId as skillId',
                'sk.SkillName as skillName',
                'b.GroupId as groupId',
                'b.GroupName as groupName'
            )
            ->orderBy('ss.CompletedAt', 'desc')
            ->get()
            ->map(function ($r) {
                $accuracy = $r->totalMarks > 0
                    ? round(($r->score / $r->totalMarks) * 100, 1)
                    : 0;
                return array_merge((array) $r, ['accuracy' => $accuracy]);
            });

        $skills = Skill::all();
        $groups = Group::all();
        $tests = Test::orderBy('TestName')->get(['TestId', 'TestName']);

        return response()->json([
            'results' => $results,
            'skills' => $skills,
            'groups' => $groups,
            'tests' => $tests,
        ]);
    }

    public function deleteSubmission(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Results', 'delete')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to delete results.'], 403);
        }

        DB::table('tblstudentsubmission')->where('SubmissionId', $id)->delete();
        return response()->json(['message' => 'Result deleted successfully']);
    }

    public function updateStudent(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Students', 'edit')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to edit students.'], 403);
        }

        $student = Student::find($id);
        $admin = $student ? null : Admin::find($id);

        if (!$student && !$admin) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $currentUser = auth()->user() ?? $request->user();
        $isTargetSuperAdmin = $admin && in_array($admin->Role, ['Super Admin', 'SuperAdmin']);
        $isSelectingSuperAdmin = in_array($request->role, ['Super Admin', 'SuperAdmin']);
        $isCurrentSuperAdmin = $currentUser && in_array($currentUser->Role ?? $currentUser->role, ['Super Admin', 'SuperAdmin']);

        $isStudent = ($student !== null);

        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'newPassword' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', 'string', 'in:Student,Admin,Super Admin,SuperAdmin'],
        ];

        if (!$isStudent) {
            $rules['username'] = ['required', 'string', 'max:255'];
        } else {
            $rules['shift'] = ['nullable', 'string', 'max:50'];
            $rules['skill'] = ['nullable', 'string', 'max:255'];
            $rules['group'] = ['nullable', 'string', 'max:255'];
            $rules['studentCode'] = ['nullable', 'string', 'max:50'];
        }

        $data = $request->validate($rules);
        $photoPath = $this->processUploadedPhoto($request->input('photo'), $request->file('photo'));

        if ($isStudent) {
            $skillName = $data['skill'] ?? 'General';
            $groupName = $data['group'] ?? 'Group A';
            $skill = Skill::firstOrCreate(['SkillName' => $skillName], ['Description' => '']);
            $group = Group::firstOrCreate(['GroupName' => $groupName]);
            $studentUpdate = [
                'FirstName' => $data['firstName'],
                'LastName' => $data['lastName'],
                'Phone' => $data['phone'],
                'StudyShift' => $data['shift'] ?? 'Morning',
                'SkillId' => $skill->SkillId,
                'GroupId' => $group->GroupId,
            ];
            if (!empty($request->input('studentCode'))) {
                $studentUpdate['StudentCode'] = $request->input('studentCode');
            }
            if ($photoPath) {
                $studentUpdate['Photo'] = $photoPath;
            }
            if ($request->has('durationMonths')) {
                $studentUpdate['DurationMonths'] = $this->parseDurationMonths($request->input('durationMonths'));
            }
            if (!empty($request->input('enrolledMonth'))) {
                $studentUpdate['EnrolledMonth'] = $request->input('enrolledMonth');
            }
            if (!empty($request->input('enrolledYear'))) {
                $studentUpdate['EnrolledYear'] = $request->input('enrolledYear');
            }
            $student->update($studentUpdate);

            return response()->json([
                'message' => 'Student updated.',
                'student' => [
                    'id' => $student->StudentId,
                    'name' => $data['firstName'] . ' ' . $data['lastName'],
                    'studentCode' => $student->StudentCode,
                    'phone' => $data['phone'],
                    'role' => 'Student',
                    'photo' => $student->Photo,
                    'skill' => $data['skill'] ?? '',
                    'group' => $data['group'] ?? '',
                    'shift' => $data['shift'] ?? '',
                    'durationMonths' => $student->DurationMonths
                ]
            ]);
        } else {
            $adminUpdate = [
                'FirstName' => $data['firstName'],
                'LastName' => $data['lastName'],
                'Phone' => $data['phone'],
                'Username' => $data['username'],
            ];
            if (!empty($data['role']) && $isCurrentSuperAdmin) {
                $adminUpdate['Role'] = $data['role'];
            }
            if (!empty($data['newPassword'])) {
                $adminUpdate['Password'] = \Illuminate\Support\Facades\Hash::make($data['newPassword']);
            }
            if ($photoPath) {
                $adminUpdate['ProfileImage'] = $photoPath;
            }
            $admin->update($adminUpdate);

            return response()->json([
                'message' => 'Admin updated.',
                'student' => [
                    'id' => $admin->AdminId,
                    'name' => $data['firstName'] . ' ' . $data['lastName'],
                    'username' => $admin->Username,
                    'phone' => $data['phone'],
                    'role' => $admin->Role,
                    'photo' => $admin->ProfileImage
                ]
            ]);
        }
    }

    public function deleteStudent(Request $request, $id)
    {
        if (!self::checkAdminPermission($request->user(), 'Students', 'delete')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to delete.'], 403);
        }

        $deletedStudent = Student::where('StudentId', $id)->delete();
        $deletedAdmin = Admin::where('AdminId', $id)->delete();

        if (!$deletedStudent && !$deletedAdmin) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function addStudent(Request $request)
    {
        if (!self::checkAdminPermission($request->user(), 'Students', 'create')) {
            return response()->json(['message' => 'Unauthorized. You do not have permission to add users.'], 403);
        }

        $currentUser = auth()->user();
        $isSelectingSuperAdmin = in_array($request->input('role'), ['Super Admin', 'SuperAdmin']);
        $isCurrentSuperAdmin = $currentUser && in_array($currentUser->role, ['Super Admin', 'SuperAdmin']);

        if ($isSelectingSuperAdmin && !$isCurrentSuperAdmin) {
            return response()->json(['message' => 'Unauthorized. Only Super Admins can create new Super Admins.'], 403);
        }

        $isStudent = $request->input('role', 'Student') === 'Student';

        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:Student,Admin,Super Admin,SuperAdmin'],
        ];

        if ($isStudent) {
            $rules['gender'] = ['required', 'string'];
            $rules['shift'] = ['required', 'string'];
            $rules['skillId'] = ['required', 'integer'];
            $rules['groupId'] = ['required', 'integer'];
            $rules['enrolledMonth'] = ['nullable', 'string'];
            $rules['enrolledYear'] = ['nullable', 'string'];
            $rules['studentCode'] = ['nullable', 'string'];
            $rules['photo'] = ['nullable'];
        } else {
            $rules['username'] = ['required', 'string', 'regex:/^\S+$/', 'unique:tbladmin,Username'];
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        $data = $request->validate($rules, [
            'username.regex' => 'Username មិនអាចមានដកឃ្លាទេ (Username cannot contain spaces).',
        ]);
        $role = $request->input('role', 'Student');
        $photoPath = $this->processUploadedPhoto($request->input('photo'), $request->file('photo'));

        if ($isStudent) {
            $year = $data['enrolledYear'] ?? date('Y');
            $studentCode = $request->input('studentCode');
            if (empty($studentCode)) {
                for ($i = 0; $i < 100; $i++) {
                    $randCode = 'RTC-' . $year . '-' . str_pad((string)mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
                    if (!Student::where('StudentCode', $randCode)->exists()) {
                        $studentCode = $randCode;
                        break;
                    }
                }
                if (empty($studentCode)) {
                    $studentCode = 'RTC-' . $year . '-' . str_pad((string)((Student::max('StudentId') ?? 0) + 1), 5, '0', STR_PAD_LEFT);
                }
            }

            Student::create([
                'StudentCode' => $studentCode,
                'UserId' => null,
                'SkillId' => $data['skillId'],
                'GroupId' => $data['groupId'],
                'FirstName' => $data['firstName'],
                'LastName' => $data['lastName'],
                'Gender' => $data['gender'],
                'StudyShift' => $data['shift'],
                'EnrolledMonth' => $data['enrolledMonth'] ?? now()->format('F'),
                'EnrolledYear' => $data['enrolledYear'] ?? date('Y'),
                'DurationMonths' => $this->parseDurationMonths($request->input('durationMonths', 1)),
                'Phone' => $data['phone'],
                'Photo' => $photoPath,
            ]);
        } else {
            Admin::create([
                'UserId' => null,
                'Username' => $data['username'],
                'Password' => \Illuminate\Support\Facades\Hash::make($data['password']),
                'Role' => $role,
                'Status' => 'Active',
                'FirstName' => $data['firstName'],
                'LastName' => $data['lastName'],
                'Phone' => $data['phone'],
                'ProfileImage' => $photoPath,
                'CreatedByUserId' => auth()->id() ?? 5,
            ]);
        }

        return response()->json(['message' => "$role created.", 'studentCode' => $studentCode ?? null]);
    }

    public function liveMonitor(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $selectFields = [
                'ss.SubmissionId as submissionId',
                'ss.StudentId as studentId',
                's.StudentCode as studentCode',
                's.FirstName as firstName',
                's.LastName as lastName',
                's.Gender as gender',
                's.StudyShift as shift',
                's.Phone as phone',
                's.SkillId as skillId',
                'sk.SkillName as skill',
                's.GroupId as groupId',
                'g.GroupName as group',
                'ss.TestId as testId',
                't.TestName as testName',
                't.DurationMinutes as durationMinutes',
                't.TotalMarks as totalMarks',
                'ss.Score as score',
                'ss.StartedAt as startedAt',
                'ss.CompletedAt as completedAt',
                'ss.Interruptions as interruptions',
                DB::raw('(SELECT COUNT(*) FROM tblquestion WHERE tblquestion.TestId = t.TestId) as totalQuestions'),
                DB::raw('(SELECT COUNT(*) FROM tblsubmissiondetail WHERE tblsubmissiondetail.SubmissionId = ss.SubmissionId AND tblsubmissiondetail.SelectedAnswerId IS NOT NULL) as answeredCount')
            ];

            $todayCompletedCount = DB::table('tblstudentsubmission')
                ->whereDate('CompletedAt', now()->toDateString())
                ->count();

            $submissions = DB::table('tblstudentsubmission as ss')
                ->join('tblstudent as s', 'ss.StudentId', '=', 's.StudentId')
                ->leftJoin('tblskill as sk', 's.SkillId', '=', 'sk.SkillId')
                ->leftJoin('tblgroup as g', 's.GroupId', '=', 'g.GroupId')
                ->join('tbltest as t', 'ss.TestId', '=', 't.TestId')
                ->select($selectFields)
                ->whereNull('ss.CompletedAt')
                ->where('ss.StartedAt', '>=', now()->subHours(12))
                ->orderBy('ss.StartedAt', 'desc')
                ->get()
                ->map(function ($r) {
                    $studentName = trim($r->firstName . ' ' . $r->lastName);
                    $isCompleted = !empty($r->completedAt);
                    $progress = $r->totalQuestions > 0 ? round(($r->answeredCount / $r->totalQuestions) * 100) : 0;
                    
                    $started = $r->startedAt ? \Carbon\Carbon::parse($r->startedAt) : now();
                    $endedTimestamp = $r->completedAt ? \Carbon\Carbon::parse($r->completedAt)->getTimestamp() : now()->getTimestamp();
                    $elapsedMinutes = (int) max(0, round(($endedTimestamp - $started->getTimestamp()) / 60));
                    $remainingMinutes = max(0, $r->durationMinutes - $elapsedMinutes);

                    return [
                        'submissionId' => $r->submissionId,
                        'studentId' => $r->studentId,
                        'studentCode' => $r->studentCode,
                        'studentName' => $studentName ?: ($r->studentCode ?? 'Candidate'),
                        'gender' => $r->gender,
                        'shift' => $r->shift,
                        'phone' => $r->phone,
                        'skillId' => $r->skillId,
                        'skill' => $r->skill,
                        'groupId' => $r->groupId,
                        'group' => $r->group,
                        'testId' => $r->testId,
                        'testName' => $r->testName,
                        'durationMinutes' => $r->durationMinutes,
                        'totalMarks' => $r->totalMarks,
                        'score' => $r->score,
                        'totalQuestions' => (int) $r->totalQuestions,
                        'answeredCount' => (int) $r->answeredCount,
                        'progress' => $progress,
                        'interruptions' => (int) ($r->interruptions ?? 0),
                        'startedAt' => $r->startedAt,
                        'completedAt' => $r->completedAt,
                        'elapsedMinutes' => $elapsedMinutes,
                        'remainingMinutes' => $remainingMinutes,
                        'status' => $isCompleted ? 'Completed' : ($remainingMinutes <= 0 ? 'Overdue' : 'In Progress'),
                    ];
                })
                ->filter(function ($item) {
                    // Only show actively ongoing sessions currently in progress
                    return $item['status'] === 'In Progress';
                })
                ->values();

            $skills = Skill::orderBy('SkillName')->get(['SkillId', 'SkillName']);
            $groups = Group::orderBy('GroupName')->get(['GroupId', 'GroupName']);
            $tests = Test::where('Status', 'Published')->orderBy('TestName')->get(['TestId', 'TestName']);

            return response()->json([
                'examinees' => $submissions,
                'activeCount' => $submissions->count(),
                'completedTodayCount' => $todayCompletedCount,
                'skills' => $skills,
                'groups' => $groups,
                'tests' => $tests,
                'serverTime' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            \Log::error('liveMonitor error: ' . $e->getMessage());
            return response()->json([
                'examinees' => [],
                'activeCount' => 0,
                'completedTodayCount' => 0,
                'skills' => Skill::orderBy('SkillName')->get(['SkillId', 'SkillName']),
                'groups' => Group::orderBy('GroupName')->get(['GroupId', 'GroupName']),
                'tests' => Test::where('Status', 'Published')->orderBy('TestName')->get(['TestId', 'TestName']),
                'serverTime' => now()->toIso8601String(),
            ]);
        }
    }

    public function forceSubmit(Request $request, $submissionId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $submission = \App\Models\StudentSubmission::with(['test.questions.answers', 'details'])->findOrFail($submissionId);

        if ($submission->CompletedAt) {
            return response()->json([
                'success' => true,
                'message' => 'Submission is already completed.',
                'score' => $submission->Score,
                'totalCorrect' => $submission->TotalCorrect,
            ]);
        }

        $test = $submission->test;
        $totalMarks = $test ? $test->TotalMarks : 100;
        $questions = $test ? $test->questions : collect();
        $totalQuestions = $questions->count();

        $details = $submission->details;
        $correctCount = 0;

        foreach ($questions as $q) {
            $detail = $details->firstWhere('QuestionId', $q->QuestionId);
            if ($detail && $detail->SelectedAnswerId) {
                $ans = $q->answers->firstWhere('AnswerId', $detail->SelectedAnswerId);
                $isCorrect = $ans ? (bool) $ans->IsCorrect : false;
                $detail->update(['IsCorrect' => $isCorrect]);
                if ($isCorrect) {
                    $correctCount++;
                }
            }
        }

        $pointsPerQuestion = $totalQuestions > 0 ? ($totalMarks / $totalQuestions) : 0;
        $finalScore = round($correctCount * $pointsPerQuestion, 2);

        $submission->update([
            'CompletedAt' => now(),
            'Score' => $finalScore,
            'TotalCorrect' => $correctCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Submission forcefully submitted and evaluated successfully.',
            'score' => $finalScore,
            'totalCorrect' => $correctCount,
        ]);
    }

    public function studentPhoto($id)
    {
        try {
            $student = DB::table('tblstudent')->where('StudentId', $id)->first(['Photo']);
            if (!$student || empty($student->Photo)) {
                return response('', 404);
            }

            $photo = $student->Photo;

            if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
                return redirect($photo);
            }
            if (str_starts_with($photo, '/uploads/')) {
                $filePath = public_path(ltrim($photo, '/'));
                if (file_exists($filePath)) {
                    return response()->file($filePath, [
                        'Cache-Control' => 'public, max-age=604800, immutable',
                    ]);
                }
            }

            $mime = 'image/jpeg';
            $binary = null;
            if (str_starts_with($photo, 'data:image/')) {
                $commaPos = strpos($photo, ',');
                if ($commaPos !== false) {
                    $header = substr($photo, 0, $commaPos);
                    if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64/', $header, $m)) {
                        $mime = $m[1];
                    }
                    $binary = base64_decode(substr($photo, $commaPos + 1));
                }
            } else {
                $binary = base64_decode($photo, true);
                if ($binary === false) {
                    $binary = $photo;
                }
            }

            if (empty($binary)) {
                return response('', 404);
            }

            return response($binary, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Length' => strlen($binary),
            ]);
        } catch (\Throwable $e) {
            return response('', 404);
        }
    }

    public function adminPhoto($id)
    {
        try {
            $adminTable = Schema::hasTable('tbladmin') ? 'tbladmin' : 'tbladminprofile';
            $adminIdCol = Schema::hasColumn($adminTable, 'AdminId') ? 'AdminId' : 'AdminProfileId';

            $admin = DB::table($adminTable)->where($adminIdCol, $id)->first(['ProfileImage']);
            if (!$admin || empty($admin->ProfileImage)) {
                return response('', 404);
            }

            $photo = $admin->ProfileImage;

            if (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')) {
                return redirect($photo);
            }
            if (str_starts_with($photo, '/uploads/')) {
                $filePath = public_path(ltrim($photo, '/'));
                if (file_exists($filePath)) {
                    return response()->file($filePath, [
                        'Cache-Control' => 'public, max-age=604800, immutable',
                    ]);
                }
            }

            $mime = 'image/jpeg';
            $binary = null;
            if (str_starts_with($photo, 'data:image/')) {
                $commaPos = strpos($photo, ',');
                if ($commaPos !== false) {
                    $header = substr($photo, 0, $commaPos);
                    if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64/', $header, $m)) {
                        $mime = $m[1];
                    }
                    $binary = base64_decode(substr($photo, $commaPos + 1));
                }
            } else {
                $binary = base64_decode($photo, true);
                if ($binary === false) {
                    $binary = $photo;
                }
            }

            if (empty($binary)) {
                return response('', 404);
            }

            return response($binary, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Content-Length' => strlen($binary),
            ]);
        } catch (\Throwable $e) {
            return response('', 404);
        }
    }

    private function processUploadedPhoto($photoInput, $uploadedFile = null): ?string
    {
        try {
            $uploadDir = public_path('uploads/profiles');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            if ($uploadedFile && $uploadedFile->isValid()) {
                $filename = time() . '_' . uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
                $uploadedFile->move($uploadDir, $filename);
                return '/uploads/profiles/' . $filename;
            }

            if (!empty($photoInput) && is_string($photoInput) && str_starts_with($photoInput, 'data:image/')) {
                $parts = explode(',', $photoInput);
                if (count($parts) === 2) {
                    $data = base64_decode($parts[1]);
                    $ext = 'jpg';
                    if (str_contains($parts[0], 'png')) $ext = 'png';
                    if (str_contains($parts[0], 'webp')) $ext = 'webp';
                    $filename = time() . '_' . uniqid() . '.' . $ext;
                    if (@file_put_contents($uploadDir . '/' . $filename, $data) !== false) {
                        return '/uploads/profiles/' . $filename;
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('processUploadedPhoto disk write failed: ' . $e->getMessage());
        }

        if (!empty($photoInput) && is_string($photoInput)) {
            return $photoInput;
        }

        return null;
    }

    public static function getRealDatabaseStorageStats(): array
    {
        return Cache::remember('real_database_storage_stats', 60, function () {
            $connection = config('database.default');
            $dbName = config("database.connections.{$connection}.database", 'online_exam_db');
            $tableCount = 0;
            $dbVersion = '';
            $grandTotalBytes = 0;

            try {
                if ($connection === 'mysql' || $connection === 'mariadb') {
                    $versionRow = DB::select("SELECT VERSION() as ver");
                    $dbVersion = !empty($versionRow) ? $versionRow[0]->ver : '';

                    // Fetch all tables & columns to calculate true octet row length
                    $columns = DB::select("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = ? ORDER BY table_name", [$dbName]);

                    $tablesMap = [];
                    foreach ($columns as $c) {
                        $tablesMap[$c->table_name][] = $c->column_name;
                    }

                    $tableCount = count($tablesMap);

                    if (!empty($tablesMap)) {
                        $subqueries = [];
                        foreach ($tablesMap as $table => $cols) {
                            $sums = array_map(fn($col) => "COALESCE(OCTET_LENGTH(`$col`), 0)", $cols);
                            $subqueries[] = "(SELECT COALESCE(SUM(" . implode(' + ', $sums) . "), 0) FROM `{$table}`)";
                        }

                        $singleSql = "SELECT (" . implode(" + \n", $subqueries) . ") as total_data_bytes";
                        $res = DB::selectOne($singleSql);
                        $totalActualBytes = (int)($res->total_data_bytes ?? 0);

                        $indexBytes = 0;
                        try {
                            $idxRes = DB::selectOne("SELECT SUM(index_length) as idx_size FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
                            $indexBytes = (int)($idxRes->idx_size ?? 0);
                        } catch (\Throwable $e) {}

                        $grandTotalBytes = $totalActualBytes + $indexBytes;
                    }

                    if ($grandTotalBytes === 0) {
                        $fallbackRes = DB::selectOne("SELECT SUM(data_length + index_length) as size, COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
                        $grandTotalBytes = (int)($fallbackRes->size ?? 0);
                        if ($tableCount === 0) {
                            $tableCount = (int)($fallbackRes->cnt ?? 0);
                        }
                    }
                } elseif ($connection === 'pgsql') {
                    $res = DB::selectOne("SELECT pg_database_size(current_database()) as size");
                    $grandTotalBytes = (int)($res->size ?? 0);
                    $tableCount = DB::table('information_schema.tables')->where('table_schema', 'public')->count();
                } else {
                    $dbPath = config('database.connections.sqlite.database', database_path('database.sqlite'));
                    $grandTotalBytes = file_exists($dbPath) ? filesize($dbPath) : 0;
                    $tableCount = DB::table('sqlite_master')->where('type', 'table')->count();
                }
            } catch (\Throwable $e) {
                \Log::warning('Error calculating real database size: ' . $e->getMessage());
                try {
                    $tables = DB::select("SHOW TABLE STATUS");
                    $tableCount = count($tables);
                    foreach ($tables as $t) {
                        $tArray = (array)$t;
                        $grandTotalBytes += ($tArray['Data_length'] ?? $tArray['data_length'] ?? 0) + ($tArray['Index_length'] ?? $tArray['index_length'] ?? 0);
                    }
                } catch (\Throwable $te) {}
            }

            $dbSizeMB = round($grandTotalBytes / 1048576, 2);
            $isTiDB = str_contains(strtolower($dbVersion), 'tidb');
            $driverLabel = $isTiDB
                ? 'TiDB Cloud Serverless'
                : ($connection === 'mysql'
                    ? 'MySQL ' . ($dbVersion ? substr($dbVersion, 0, 6) : 'Database')
                    : ($connection === 'mariadb' ? 'MariaDB Hosting' : 'SQLite Database'));

            return [
                'bytes' => $grandTotalBytes,
                'usedMB' => $dbSizeMB,
                'tableCount' => $tableCount,
                'driver' => $driverLabel,
                'dbName' => $dbName,
                'version' => $dbVersion,
            ];
        });
    }

    public static function runSystemHealthCheck(): array
    {
        $dbStatus = 'Healthy';
        $dbPingMs = 0;
        $dbError = null;
        $dbVersion = '';
        $engine = 'TiDB Cloud Serverless';

        try {
            $dbStart = microtime(true);
            $ping = DB::selectOne('SELECT 1 as ping');
            $dbPingMs = round((microtime(true) - $dbStart) * 1000, 1);

            $v = DB::selectOne('SELECT VERSION() as ver');
            $dbVersion = $v->ver ?? '';
            if (str_contains(strtolower($dbVersion), 'tidb')) {
                $engine = 'TiDB Cloud Serverless';
            } elseif (config('database.default') === 'mysql') {
                $engine = 'MySQL ' . substr($dbVersion, 0, 6);
            }
        } catch (\Throwable $e) {
            $dbStatus = 'Error';
            $dbError = $e->getMessage();
        }

        // Cache & Storage Test
        $cacheStatus = 'Healthy';
        $cachePingMs = 0;
        try {
            $cStart = microtime(true);
            Cache::put('health_check_test', time(), 10);
            $cached = Cache::get('health_check_test');
            $cachePingMs = round((microtime(true) - $cStart) * 1000, 1);
        } catch (\Throwable $e) {
            $cacheStatus = 'Degraded';
        }

        // Auth & Sessions
        $sessionCount = 0;
        try {
            if (Schema::hasTable('sessions')) {
                $sessionCount = DB::table('sessions')->count();
            }
        } catch (\Throwable $e) {}

        // Serverless & Environment
        $memUsageMB = round(memory_get_usage(true) / 1048576, 2);
        $phpVer = PHP_VERSION;
        $region = env('VERCEL_REGION', 'sin1 (Singapore)');

        return [
            'database' => [
                'status' => $dbStatus,
                'label' => $dbPingMs > 0 ? "Healthy · {$dbPingMs}ms" : 'Healthy',
                'pingMs' => $dbPingMs,
                'engine' => $engine,
                'version' => $dbVersion,
                'error' => $dbError,
            ],
            'api' => [
                'status' => 'Healthy',
                'label' => $cachePingMs > 0 ? "Healthy · {$cachePingMs}ms" : 'Healthy · Live',
                'gateway' => 'Laravel 11 REST',
                'latencyMs' => $cachePingMs,
            ],
            'auth' => [
                'status' => 'Healthy',
                'label' => $sessionCount > 0 ? "Healthy · {$sessionCount} Sessions" : 'Healthy · Active',
                'activeSessions' => $sessionCount,
                'protection' => 'CSRF & Bcrypt Hash',
            ],
            'platform' => [
                'status' => 'Operational',
                'label' => 'Operational',
                'environment' => 'Vercel Serverless',
                'region' => $region,
                'phpVersion' => "PHP {$phpVer}",
                'memoryUsage' => "{$memUsageMB} MB",
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    public function systemHealthCheck(Request $request)
    {
        return response()->json(self::runSystemHealthCheck());
    }

    private function parseDurationMonths($input): int
    {
        if (empty($input)) return 1;
        if (is_numeric($input)) return (int)$input;
        $str = (string)$input;
        if (preg_match('/(\d+)\s*(ឆ្នាំ|year)/iu', $str, $m)) {
            return (int)$m[1] * 12;
        }
        if (preg_match('/(\d+)/', $str, $m)) {
            return (int)$m[1];
        }
        return 1;
    }
}

