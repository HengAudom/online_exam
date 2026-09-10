<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

// ─── Telegram Bot Integration ────────────────────────────────────────────────
Route::match(['get', 'post'], '/api/telegram/get-chat-id', [TelegramBotController::class, 'getChatId']);
Route::match(['get', 'post'], '/api/telegram/test-send', [TelegramBotController::class, 'testSend']);
Route::match(['get', 'post'], '/api/telegram/webhook', [TelegramBotController::class, 'webhook']);
Route::match(['get', 'post'], '/api/telegram/poll-once', [TelegramBotController::class, 'pollUpdates']);
Route::match(['get', 'post'], '/api/telegram/set-webhook', [TelegramBotController::class, 'setWebhook']);
Route::match(['get', 'post'], '/api/telegram/delete-webhook', [TelegramBotController::class, 'deleteWebhook']);
Route::match(['get', 'post'], '/api/student/telegram/unlink', [TelegramBotController::class, 'unlinkStudent']);
Route::match(['get', 'post'], '/api/student/telegram/manual-link', [TelegramBotController::class, 'manualLinkStudent']);
Route::match(['get', 'post'], '/api/telegram/sync-link', [TelegramBotController::class, 'syncLinkDirect']);
Route::match(['get', 'post'], '/api/telegram/sync-unlink', [TelegramBotController::class, 'syncUnlinkDirect']);
Route::match(['get', 'post'], '/api/telegram/sync-students', [TelegramBotController::class, 'syncAllStudentsToGas']);
Route::match(['get', 'post'], '/sync-bot', [TelegramBotController::class, 'syncAllStudentsToGas']);
Route::match(['get', 'post'], '/api/telegram/student-results', [TelegramBotController::class, 'getStudentResultsApi']);
Route::match(['get', 'post'], '/api/telegram/submission-questions', [TelegramBotController::class, 'getSubmissionQuestionsApi']);


// ─── Auth & Public ─────────────────────────────────────────────────────────────
Route::get('/api/public-settings', [AdminController::class, 'publicSettings']);
Route::get('/api/skills-groups', [AdminController::class, 'skillsGroups']);
Route::get('/api/student-photo/{id}', [AdminController::class, 'studentPhoto']);
Route::get('/api/admin-photo/{id}', [AdminController::class, 'adminPhoto']);
Route::get('/system-repair', function () {
    $results = [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'db_connection' => 'checking...',
        'repairs' => [],
        'checks' => [],
        'tables_found' => [],
    ];

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $results['db_connection'] = 'Connected successfully to ' . \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
    } catch (\Throwable $e) {
        $results['db_connection'] = 'FAILED: ' . $e->getMessage();
        return response()->json($results, 500);
    }

    try {
        $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
        foreach ($tables as $t) {
            $tArr = (array)$t;
            $results['tables_found'][] = reset($tArr);
        }
    } catch (\Throwable $e) {
        $results['checks']['show_tables_error'] = $e->getMessage();
    }

    $existingTablesLower = array_map('strtolower', $results['tables_found']);

    foreach (['tblstudent', 'tblStudent'] as $tbl) {
        if (in_array(strtolower($tbl), $existingTablesLower)) {
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$tbl}` MODIFY COLUMN `UserId` BIGINT UNSIGNED NULL DEFAULT NULL");
                $results['repairs'][] = "Set {$tbl}.UserId to NULL DEFAULT NULL";
            } catch (\Throwable $e) {
                $results['checks']["{$tbl}_alter_notice"] = $e->getMessage();
            }
        }
    }

    foreach (['tblgroup', 'tblGroup'] as $tbl) {
        if (in_array(strtolower($tbl), $existingTablesLower)) {
            try {
                $cols = \Illuminate\Support\Facades\Schema::getColumnListing($tbl);
                $colsLower = array_map('strtolower', $cols);
                if (!in_array('startdate', $colsLower)) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$tbl}` ADD COLUMN `StartDate` DATE NULL AFTER `GroupName`");
                    $results['repairs'][] = "Added {$tbl}.StartDate";
                }
                if (!in_array('enddate', $colsLower)) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$tbl}` ADD COLUMN `EndDate` DATE NULL AFTER `StartDate`");
                    $results['repairs'][] = "Added {$tbl}.EndDate";
                }
            } catch (\Throwable $e) {
                $results['checks']["{$tbl}_columns_notice"] = $e->getMessage();
            }
        }
    }

    foreach (['tbladmin', 'tblAdmin'] as $tbl) {
        if (in_array(strtolower($tbl), $existingTablesLower)) {
            try {
                $cols = array_map('strtolower', \Illuminate\Support\Facades\Schema::getColumnListing($tbl));
                if (in_array('userid', $cols)) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$tbl}` MODIFY COLUMN `UserId` BIGINT UNSIGNED NULL DEFAULT NULL");
                    $results['repairs'][] = "Set {$tbl}.UserId to NULL DEFAULT NULL";
                }
                if (in_array('createdbyuserid', $cols)) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$tbl}` MODIFY COLUMN `CreatedByUserId` BIGINT UNSIGNED NULL DEFAULT NULL");
                    $results['repairs'][] = "Set {$tbl}.CreatedByUserId to NULL DEFAULT NULL";
                }
            } catch (\Throwable $e) {
                $results['checks']["{$tbl}_alter_notice"] = $e->getMessage();
            }
        }
    }

    foreach (['tbltest', 'tblTest'] as $tbl) {
        if (in_array(strtolower($tbl), $existingTablesLower)) {
            try {
                $cols = array_map('strtolower', \Illuminate\Support\Facades\Schema::getColumnListing($tbl));
                if (in_array('createdbyuserid', $cols)) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `{$tbl}` MODIFY COLUMN `CreatedByUserId` BIGINT UNSIGNED NULL DEFAULT NULL");
                    $results['repairs'][] = "Set {$tbl}.CreatedByUserId to NULL DEFAULT NULL";
                }
            } catch (\Throwable $e) {
                $results['checks']["{$tbl}_alter_notice"] = $e->getMessage();
            }
        }
    }

    $profileDir = public_path('uploads/profiles');
    if (!is_dir($profileDir)) {
        @mkdir($profileDir, 0777, true);
        $results['repairs'][] = "Created directory {$profileDir}";
    }
    $results['checks']['uploads_profiles_writable'] = is_writable($profileDir);

    try {
        $studentTable = in_array('tblstudent', $results['tables_found']) ? 'tblstudent' : 'tblStudent';
        $results['checks']['student_count'] = \Illuminate\Support\Facades\DB::table($studentTable)->count();
    } catch (\Throwable $e) {
        $results['checks']['student_query_error'] = $e->getMessage();
    }

    $results['status'] = 'COMPLETED';
    return response()->json($results, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});
Route::get('/api/system-repair', function () {
    return redirect('/system-repair');
});
Route::post('/api/check-identifier', [AuthController::class, 'checkIdentifier']);
Route::post('/api/register', [AuthController::class, 'register']);
Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/logout', [AuthController::class, 'logout']);
Route::post('/api/password/verify-identity', [AuthController::class, 'verifyIdentity']);
Route::post('/api/password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/api/password/reset', [AuthController::class, 'resetPassword']);
Route::get('/api/profile', [AuthController::class, 'profile']);
Route::match(['get', 'post'], '/api/profile/update', [AuthController::class, 'updateProfile']);
Route::match(['get', 'post'], '/api/profile/upload-image', [AuthController::class, 'uploadProfileImage']);
Route::match(['get', 'post'], '/api/profile/change-password', [AuthController::class, 'changePassword']);

Route::get('/api/admin/dashboard', [AdminController::class, 'dashboard']);
Route::get('/api/admin/system-health-check', [AdminController::class, 'systemHealthCheck']);
Route::get('/api/admin/students', [AdminController::class, 'students']);
Route::post('/api/admin/students', [AdminController::class, 'addStudent']);
Route::put('/api/admin/students/{id}', [AdminController::class, 'updateStudent']);
Route::delete('/api/admin/students/{id}', [AdminController::class, 'deleteStudent']);

Route::get('/api/admin/skills-groups', [AdminController::class, 'skillsGroups']);
Route::post('/api/admin/skills', [AdminController::class, 'addSkill']);
Route::put('/api/admin/skills/{id}', [AdminController::class, 'updateSkill']);
Route::delete('/api/admin/skills/{id}', [AdminController::class, 'deleteSkill']);
Route::post('/api/admin/groups', [AdminController::class, 'addGroup']);
Route::put('/api/admin/groups/{id}', [AdminController::class, 'updateGroup']);
Route::delete('/api/admin/groups/{id}', [AdminController::class, 'deleteGroup']);
Route::post('/api/admin/durations', [AdminController::class, 'addDuration']);
Route::put('/api/admin/durations/{id}', [AdminController::class, 'updateDuration']);
Route::delete('/api/admin/durations/{id}', [AdminController::class, 'deleteDuration']);

Route::get('/api/admin/tests', [AdminController::class, 'tests']);
Route::post('/api/admin/tests', [TestController::class, 'store']);
Route::post('/api/admin/tests/parse-doc', [TestController::class, 'parseDoc']);
Route::get('/api/admin/tests/{id}', [TestController::class, 'show']);
Route::get('/api/admin/tests/{id}/export-word', [TestController::class, 'exportWord']);
Route::get('/api/admin/tests/{id}/export-txt', [TestController::class, 'exportTxt']);
Route::put('/api/admin/tests/{id}', [TestController::class, 'update']);
Route::delete('/api/admin/tests/{id}', [TestController::class, 'destroy']);

Route::get('/api/admin/results', [AdminController::class, 'results']);
Route::get('/api/admin/results/{id}', [ResultController::class, 'submissionDetail']);
Route::delete('/api/admin/results/{id}', [AdminController::class, 'deleteSubmission']);

Route::get('/api/admin/live-monitor', [AdminController::class, 'liveMonitor']);
Route::post('/api/admin/live-monitor/{id}/force-submit', [AdminController::class, 'forceSubmit']);

// ─── Super Admin Dedicated Endpoints ──────────────────────────────────────────
Route::get('/api/admin/audit-logs', [AdminController::class, 'auditLogs']);
Route::delete('/api/admin/audit-logs', [AdminController::class, 'clearAuditLogs']);
Route::get('/api/admin/roles-permissions', [AdminController::class, 'rolesPermissions']);
Route::post('/api/admin/roles-permissions', [AdminController::class, 'saveRolesPermissions']);
Route::get('/api/admin/system-settings', [AdminController::class, 'systemSettings']);
Route::post('/api/admin/system-settings', [AdminController::class, 'saveSystemSettings']);

// ─── Exam (Student) ───────────────────────────────────────────────────────────
Route::get('/api/exam/{testId}/start', [ExamController::class, 'start']);
Route::get('/api/exam/{submissionId}/status', [ExamController::class, 'checkStatus']);
Route::post('/api/exam/answer', [ExamController::class, 'saveAnswer']);
Route::post('/api/exam/interruption', [ExamController::class, 'recordInterruption']);
Route::post('/api/exam/{submissionId}/complete', [ExamController::class, 'complete']);

// ─── Results (Student) ────────────────────────────────────────────────────────
Route::get('/api/student/results', [ResultController::class, 'studentResults']);
Route::get('/api/student/results/{id}', [ResultController::class, 'submissionDetail']);

// ─── PWA & Asset Handlers (Guaranteed HTTP 200 & MIME Type for PWA WebAPK) ──
Route::get('/sw.js', function () {
    $path = public_path('sw.js');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/javascript; charset=utf-8',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
});

Route::get('/manifest.json', function () {
    $path = public_path('manifest.json');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
});

Route::get('/manifest.webmanifest', function () {
    $path = public_path('manifest.webmanifest');
    if (! file_exists($path)) {
        $path = public_path('manifest.json');
    }

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
});

Route::get('/pwa-{size}.png', function (string $size) {
    $allowed = [
        '192',
        '512',
        'maskable-192',
        'maskable-512',
    ];

    abort_unless(in_array($size, $allowed, true), 404);

    $path = public_path("pwa-{$size}.png");

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/apple-touch-icon.png', function () {
    $path = public_path('apple-touch-icon.png');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/favicon.ico', function () {
    $path = public_path('favicon.ico');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'image/x-icon',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/favicon.png', function () {
    $path = public_path('favicon.png');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'application/xml; charset=utf-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
});

Route::get('/robots.txt', function () {
    $path = public_path('robots.txt');

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'text/plain; charset=utf-8',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

// ─── SPA Catch-all ────────────────────────────────────────────────────────────
Route::view('/{any}', 'welcome')->where('any', '.*');
