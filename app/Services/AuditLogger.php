<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    /**
     * Record an audit log entry persistently.
     */
    public static function log(
        string $action,
        string $module,
        ?string $target = null,
        ?string $details = null,
        string $status = 'Success',
        ?Request $request = null,
        mixed $user = null
    ): void {
        try {
            $req = $request ?: request();
            $currentUser = $user ?: Auth::user() ?: ($req ? $req->user() : null);

            $userId = null;
            $userName = 'System';
            $userRole = 'System';

            if ($currentUser) {
                $userId = $currentUser->id ?? $currentUser->AdminId ?? $currentUser->StudentId ?? null;
                $displayName = trim(($currentUser->FirstName ?? '') . ' ' . ($currentUser->LastName ?? ''));
                $userName = $displayName ?: ($currentUser->Username ?? $currentUser->StudentCode ?? $currentUser->name ?? 'User');
                $userRole = $currentUser->Role ?? $currentUser->role ?? ($currentUser instanceof \App\Models\Student ? 'Student' : 'User');
            }

            $ip = $req ? $req->ip() : '127.0.0.1';
            $ua = $req ? substr((string)$req->userAgent(), 0, 255) : null;
            $now = now();

            // 1. Persist to Database if table exists
            $dbSuccess = false;
            try {
                if (Schema::hasTable('tblauditlog')) {
                    AuditLog::create([
                        'UserId' => $userId,
                        'UserName' => $userName,
                        'UserRole' => $userRole,
                        'Action' => $action,
                        'Module' => $module,
                        'Target' => $target,
                        'Status' => $status,
                        'Details' => $details,
                        'IpAddress' => $ip,
                        'UserAgent' => $ua,
                        'CreatedAt' => $now,
                    ]);
                    $dbSuccess = true;
                }
            } catch (\Throwable $dbe) {
                Log::warning('AuditLogger DB write notice: ' . $dbe->getMessage());
            }

            // 2. Also keep in JSON fallback for resiliency (e.g. serverless or DB downtime)
            self::appendJsonLog([
                'id' => 'audit-' . uniqid(),
                'userId' => $userId,
                'user' => $userName,
                'role' => $userRole,
                'action' => $action,
                'module' => $module,
                'target' => $target ?: '-',
                'status' => $status,
                'details' => $details,
                'ip' => $ip,
                'date' => $now->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AuditLogger failed to log: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve aggregated audit logs.
     */
    public static function getLogs(?string $clearedAt = null, int $limit = 350): Collection
    {
        if ($clearedAt === null) {
            try {
                $metaFile = storage_path('app/audit_logs_meta.json');
                if (file_exists($metaFile)) {
                    $meta = json_decode(file_get_contents($metaFile), true);
                    $clearedAt = $meta['cleared_at'] ?? null;
                }
            } catch (\Throwable $e) {}
        }

        $dbLogs = collect();

        try {
            if (Schema::hasTable('tblauditlog')) {
                $query = DB::table('tblauditlog');
                if ($clearedAt) {
                    $query->where('CreatedAt', '>', $clearedAt);
                }
                $dbLogs = $query->orderBy('CreatedAt', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(fn($r) => [
                        'id' => 'db-' . $r->AuditLogId,
                        'date' => $r->CreatedAt,
                        'user' => $r->UserName ?: 'System',
                        'role' => $r->UserRole ?: 'User',
                        'action' => $r->Action,
                        'module' => $r->Module,
                        'target' => $r->Target ?: '-',
                        'status' => $r->Status ?: 'Success',
                        'details' => $r->Details ?: '',
                        'ip' => $r->IpAddress ?: '',
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Error querying tblauditlog: ' . $e->getMessage());
        }

        // Read file-based logs (Authentication login/logout + Fallback logs)
        $fileLogs = collect();
        try {
            $jsonFile = storage_path('app/login_logs.json');
            if (file_exists($jsonFile)) {
                $authEntries = json_decode(file_get_contents($jsonFile), true) ?: [];
                $fileLogs = collect($authEntries)->map(fn($l) => [
                    'id' => $l['id'] ?? ('auth-' . uniqid()),
                    'date' => $l['date'] ?? now()->toDateTimeString(),
                    'user' => $l['displayName'] ?: ($l['username'] ?? 'Unknown'),
                    'role' => $l['role'] ?? 'User',
                    'action' => ($l['status'] ?? '') === 'Failed' ? 'Failed Login Attempt' : (($l['status'] ?? '') === 'Logged Out' ? 'User Logout' : 'User Login'),
                    'module' => 'Authentication',
                    'target' => '@' . ($l['username'] ?? 'unknown'),
                    'status' => ($l['status'] ?? '') === 'Failed' ? 'Failed' : (($l['status'] ?? '') === 'Logged Out' ? 'Logged Out' : 'Success'),
                    'details' => $l['details'] ?? ("IP: " . ($l['ipAddress'] ?? '127.0.0.1')),
                    'ip' => $l['ipAddress'] ?? '',
                ]);
            }

            $auditJson = storage_path('app/audit_logs.json');
            if (file_exists($auditJson)) {
                $extraEntries = json_decode(file_get_contents($auditJson), true) ?: [];
                $fileLogs = $fileLogs->concat($extraEntries);
            }
        } catch (\Throwable $e) {
            Log::warning('Error reading file logs: ' . $e->getMessage());
        }

        // Also fetch legacy historical records if any exist
        $legacyLogs = self::getLegacyHistoricalLogs($clearedAt);

        if ($clearedAt) {
            $fileLogs = $fileLogs->filter(fn($l) => isset($l['date']) && $l['date'] > $clearedAt);
        }

        // Combine DB, File, and Legacy logs, deduplicating if identical date+user+action+target
        $merged = $dbLogs->concat($fileLogs)->concat($legacyLogs)
            ->unique(fn($item) => ($item['date'] ?? '') . '-' . ($item['user'] ?? '') . '-' . ($item['action'] ?? '') . '-' . ($item['target'] ?? ''))
            ->sortByDesc('date')
            ->values();

        return $merged->take($limit);
    }

    /**
     * Fetch legacy historical logs from tbltest, tblstudent, tblstudentsubmission, tbladmin
     */
    private static function getLegacyHistoricalLogs(?string $clearedAt): Collection
    {
        $legacy = collect();
        try {
            if (Schema::hasTable('tblstudentsubmission')) {
                $subQuery = DB::table('tblstudentsubmission as ss')
                    ->join('tblstudent as s', 'ss.StudentId', '=', 's.StudentId')
                    ->join('tbltest as t', 'ss.TestId', '=', 't.TestId')
                    ->whereNotNull('ss.CompletedAt');
                if ($clearedAt) {
                    $subQuery->where('ss.CompletedAt', '>', $clearedAt);
                }
                $subs = $subQuery->orderBy('ss.CompletedAt', 'desc')->limit(40)
                    ->select('ss.SubmissionId', 's.FirstName', 's.LastName', 't.TestName', 'ss.Score', 'ss.CompletedAt as Date')
                    ->get()
                    ->map(fn($r) => [
                        'id' => 'legacy-sub-' . $r->SubmissionId,
                        'date' => $r->Date,
                        'user' => trim("{$r->FirstName} {$r->LastName}") ?: 'Student',
                        'role' => 'Student',
                        'action' => 'Exam Submission',
                        'module' => 'Submissions',
                        'target' => $r->TestName,
                        'status' => 'Completed',
                        'details' => "Score: {$r->Score} points",
                        'ip' => '',
                    ]);
                $legacy = $legacy->concat($subs);
            }

            if (Schema::hasTable('tbltest')) {
                $testQuery = DB::table('tbltest');
                if ($clearedAt) {
                    $testQuery->where('created_at', '>', $clearedAt);
                }
                $tests = $testQuery->orderBy('created_at', 'desc')->limit(30)
                    ->select('TestId', 'TestName', 'created_at as Date', 'Status')
                    ->get()
                    ->map(fn($r) => [
                        'id' => 'legacy-test-' . $r->TestId,
                        'date' => $r->Date,
                        'user' => 'Admin',
                        'role' => 'Admin',
                        'action' => 'Exam Created',
                        'module' => 'Exams',
                        'target' => $r->TestName,
                        'status' => $r->Status ?: 'Published',
                        'details' => "Exam status: {$r->Status}",
                        'ip' => '',
                    ]);
                $legacy = $legacy->concat($tests);
            }
        } catch (\Throwable $e) {
            Log::warning('Legacy historical logs retrieval notice: ' . $e->getMessage());
        }

        return $legacy;
    }

    /**
     * Clear all logs across database and file systems.
     */
    public static function clear(): bool
    {
        try {
            if (Schema::hasTable('tblauditlog')) {
                DB::table('tblauditlog')->truncate();
            }

            $loginFile = storage_path('app/login_logs.json');
            if (file_exists($loginFile)) {
                @file_put_contents($loginFile, json_encode([]));
            }

            $auditFile = storage_path('app/audit_logs.json');
            if (file_exists($auditFile)) {
                @file_put_contents($auditFile, json_encode([]));
            }

            $metaFile = storage_path('app/audit_logs_meta.json');
            @file_put_contents($metaFile, json_encode([
                'cleared_at' => now()->toDateTimeString(),
            ]));

            return true;
        } catch (\Throwable $e) {
            Log::error('AuditLogger::clear failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Append to JSON log file.
     */
    private static function appendJsonLog(array $entry): void
    {
        try {
            $dir = storage_path('app');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = storage_path('app/audit_logs.json');
            $logs = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];

            array_unshift($logs, $entry);
            $logs = array_slice($logs, 0, 300); // keep latest 300 entries in file
            @file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            // Silently ignore disk errors
        }
    }
}
