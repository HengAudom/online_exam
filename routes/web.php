<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

// ─── Auth ─────────────────────────────────────────────────────────────────────
Route::post('/api/register',          [AuthController::class, 'register']);
Route::post('/api/login',             [AuthController::class, 'login']);
Route::post('/api/logout',            [AuthController::class, 'logout']);
Route::post('/api/password/forgot',   [AuthController::class, 'forgotPassword']);
Route::post('/api/password/reset',    [AuthController::class, 'resetPassword']);
Route::get('/api/profile',            [AuthController::class, 'profile']);
Route::post('/api/profile/update',    [AuthController::class, 'updateProfile']);
Route::post('/api/profile/upload-image', [AuthController::class, 'uploadProfileImage']);
Route::post('/api/profile/change-password', [AuthController::class, 'changePassword']);

// ─── Admin ────────────────────────────────────────────────────────────────────
Route::get('/api/admin/dashboard',            [AdminController::class, 'dashboard']);
Route::get('/api/admin/students',             [AdminController::class, 'students']);
Route::post('/api/admin/students',            [AdminController::class, 'addStudent']);
Route::put('/api/admin/students/{id}',        [AdminController::class, 'updateStudent']);
Route::delete('/api/admin/students/{id}',     [AdminController::class, 'deleteStudent']);

Route::get('/api/admin/skills-batches',       [AdminController::class, 'skillsBatches']);
Route::post('/api/admin/skills',              [AdminController::class, 'addSkill']);
Route::put('/api/admin/skills/{id}',          [AdminController::class, 'updateSkill']);
Route::delete('/api/admin/skills/{id}',       [AdminController::class, 'deleteSkill']);
Route::post('/api/admin/batches',             [AdminController::class, 'addBatch']);
Route::put('/api/admin/batches/{id}',         [AdminController::class, 'updateBatch']);
Route::delete('/api/admin/batches/{id}',      [AdminController::class, 'deleteBatch']);

Route::get('/api/admin/tests',                [AdminController::class, 'tests']);
Route::post('/api/admin/tests',               [TestController::class,  'store']);
Route::get('/api/admin/tests/{id}',           [TestController::class,  'show']);
Route::put('/api/admin/tests/{id}',           [TestController::class,  'update']);
Route::delete('/api/admin/tests/{id}',        [TestController::class,  'destroy']);
Route::get('/api/admin/tests/{id}/export',    [TestController::class,  'export']);
Route::post('/api/admin/tests/import',        [TestController::class,  'import']);

Route::get('/api/admin/results',              [AdminController::class, 'results']);
Route::delete('/api/admin/results/{id}',        [AdminController::class, 'deleteSubmission']);

// ─── Exam (Student) ───────────────────────────────────────────────────────────
Route::get('/api/exam/{testId}/start',        [ExamController::class,  'start']);
Route::post('/api/exam/answer',               [ExamController::class,  'saveAnswer']);
Route::post('/api/exam/{submissionId}/complete', [ExamController::class, 'complete']);

// ─── Results (Student) ────────────────────────────────────────────────────────
Route::get('/api/student/results',            [ResultController::class, 'studentResults']);
Route::get('/api/student/results/{id}',       [ResultController::class, 'submissionDetail']);

// ─── SPA Catch-all ────────────────────────────────────────────────────────────
Route::view('/{any}', 'welcome')->where('any', '.*');
