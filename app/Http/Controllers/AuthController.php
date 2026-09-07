<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Skill;
use App\Models\Student;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $settings = AdminController::getSystemSettings();
        if (isset($settings['allowRegistration']) && !$settings['allowRegistration']) {
            return response()->json([
                'message' => 'ការចុះឈ្មោះបង្កើតគណនីដោយខ្លួនឯងត្រូវបានបិទជាបណ្ដោះអាសន្នដោយ Administrator (Self-registration is currently disabled).'
            ], 403);
        }

        $data = $request->validate([
            'studentCode' => ['nullable', 'string', 'max:50'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'string', 'max:50'],
            'shift' => ['required', 'string', 'max:50'],
            'skill' => ['required', 'string', 'max:255'],
            'group' => ['required', 'string', 'max:255'],
            'intakeMonth' => ['nullable', 'string', 'max:50'],
            'intakeYear' => ['nullable', 'string', 'max:10'],
            'durationMonths' => ['nullable'],
            'photo' => ['nullable'],
        ], [
            'firstName.required' => 'សូមបំពេញនាមខ្លួន (First Name is required).',
            'lastName.required' => 'សូមបំពេញគោត្តនាម (Last Name is required).',
            'phone.required' => 'សូមបំពេញលេខទូរស័ព្ទ (Phone is required).',
            'gender.required' => 'សូមជ្រើសរើសភេទ (Gender is required).',
            'shift.required' => 'សូមជ្រើសរើសវេនសិក្សា (Study shift is required).',
            'skill.required' => 'សូមជ្រើសរើសជំនាញ (Skill is required).',
            'group.required' => 'សូមជ្រើសរើសក្រុម (Group is required).',
        ]);

        try {
            return DB::transaction(function () use ($request, $data) {
                $skill = Skill::firstOrCreate(
                    ['SkillName' => $data['skill']],
                    ['Description' => '']
                );

                $group = Group::firstOrCreate(
                    ['GroupName' => $data['group']]
                );

                // Process profile photo safely (never throws)
                $photoPath = $this->processUploadedPhoto($request->input('photo'), $request->file('photo'));

                // Use requested student code if provided and unique, otherwise generate unique student ID
                $requestedCode = trim($request->input('studentCode', ''));
                if (!empty($requestedCode) && !Student::where('StudentCode', $requestedCode)->exists()) {
                    $studentCode = $requestedCode;
                } else {
                    $studentCode = $this->generateStudentCode($data['intakeYear'] ?? date('Y'));
                }

                $durationMonths = $this->parseDurationMonths($data['durationMonths'] ?? 4);

                $dummyStudent = new Student();
                $table = $dummyStudent->getTable();

                // Proactively ensure UserId column is nullable if it exists
                try {
                    DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `UserId` BIGINT UNSIGNED NULL DEFAULT NULL");
                } catch (\Throwable $t) {}

                $availableColumns = [];
                try {
                    $availableColumns = Schema::getColumnListing($table);
                } catch (\Throwable $e) {
                    $availableColumns = [];
                }

                $payload = [
                    'StudentCode' => $studentCode,
                    'SkillId' => $skill->SkillId,
                    'GroupId' => $group->GroupId,
                    'FirstName' => $data['firstName'],
                    'LastName' => $data['lastName'],
                    'Gender' => $data['gender'],
                    'StudyShift' => $data['shift'],
                    'EnrolledMonth' => $data['intakeMonth'] ?? now()->format('F'),
                    'EnrolledYear' => $data['intakeYear'] ?? date('Y'),
                    'DurationMonths' => $durationMonths,
                    'Phone' => $data['phone'],
                    'Photo' => $photoPath,
                ];

                if (empty($availableColumns) || in_array('UserId', $availableColumns) || in_array('userid', array_map('strtolower', $availableColumns))) {
                    $payload['UserId'] = null;
                }

                if (!empty($availableColumns)) {
                    $lowerCols = array_map('strtolower', $availableColumns);
                    $filteredPayload = [];
                    foreach ($payload as $key => $val) {
                        if (in_array(strtolower($key), $lowerCols)) {
                            $filteredPayload[$key] = $val;
                        }
                    }
                    $payload = $filteredPayload;
                }

                try {
                    $student = Student::create($payload);
                } catch (\Throwable $createEx) {
                    // If error involves UserId constraint, remove UserId and retry
                    if (str_contains(strtolower($createEx->getMessage()), 'userid')) {
                        unset($payload['UserId']);
                        $student = Student::create($payload);
                    } else {
                        throw $createEx;
                    }
                }

                return response()->json([
                    'message' => 'Registration successful. Your Student ID is ' . $studentCode . '. Please sign in to take exams.',
                    'studentCode' => $studentCode,
                    'student' => $student,
                ], 201);
            });
        } catch (\Throwable $e) {
            @file_put_contents(storage_path('app/reg_error.log'), date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
            \Log::error('Registration exception: ' . $e->getMessage());

            // If the student was already created in the DB (e.g. from duplicate submit or race condition)
            $requestedCode = trim($request->input('studentCode', ''));
            if (!empty($requestedCode)) {
                try {
                    $existing = Student::where('StudentCode', $requestedCode)->first();
                    if ($existing) {
                        return response()->json([
                            'message' => 'Registration successful. Your Student ID is ' . $existing->StudentCode . '. Please sign in to take exams.',
                            'studentCode' => $existing->StudentCode,
                            'student' => $existing,
                        ], 200);
                    }
                } catch (\Throwable $ex) {}
            }

            return response()->json([
                'message' => 'មានបញ្ហាពេលចុះឈ្មោះ (Registration error: ' . $e->getMessage() . ')',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public static function recordLoginAudit(
        ?int $userId,
        string $username,
        string $role,
        ?string $displayName,
        string $status,
        ?string $details = null,
        ?Request $request = null
    ) {
        try {
            $ip = $request ? $request->ip() : request()->ip();
            $userAgent = $request ? $request->userAgent() : request()->userAgent();

            $dir = storage_path('app');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $file = storage_path('app/login_logs.json');
            $logs = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];

            array_unshift($logs, [
                'id' => 'auth-' . uniqid(),
                'userId' => $userId,
                'username' => $username,
                'role' => $role,
                'displayName' => $displayName ?: $username,
                'ipAddress' => $ip,
                'userAgent' => $userAgent,
                'status' => $status,
                'details' => $details,
                'date' => now()->toDateTimeString()
            ]);

            // Keep latest 300 logs
            $logs = array_slice($logs, 0, 300);
            file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            \Log::warning('Could not write login_logs.json: ' . $e->getMessage());
        }
    }

    public function checkIdentifier(Request $request)
    {
        $identifier = trim($request->input('identifier') ?? $request->input('username') ?? '');
        if ($identifier === '') {
            return response()->json(['requiresPassword' => false, 'role' => null, 'exists' => false]);
        }

        // 1. Query Database for Admin / Super Admin (tbladmin)
        $admin = Admin::whereRaw('LOWER(Username) = ?', [strtolower($identifier)])
            ->select('AdminId', 'Username', 'Role')
            ->first();

        if ($admin) {
            return response()->json([
                'requiresPassword' => true,
                'role' => $admin->Role ?? 'Admin',
                'exists' => true
            ]);
        }

        // 2. Query Database for Student (tblstudent)
        $student = self::findStudentByIdentifier($identifier);

        if ($student) {
            return response()->json([
                'requiresPassword' => false,
                'role' => 'Student',
                'exists' => true
            ]);
        }

        return response()->json([
            'requiresPassword' => false,
            'role' => null,
            'exists' => false
        ]);
    }

    public function login(Request $request)
    {
        $identifier = trim($request->input('identifier') ?? $request->input('username') ?? '');
        $password = $request->input('password');
        $lang = $request->input('lang') === 'en' ? 'en' : 'kh';

        if ($identifier === '') {
            return response()->json([
                'message' => $lang === 'en' ? 'Please enter Student ID or Username.' : 'សូមបញ្ចូល Student ID ឬ Username'
            ], 422);
        }

        try {
            // 1. Try Admin / Super Admin Login (strictly from tbladmin)
            $adminUser = Admin::whereRaw('LOWER(Username) = ?', [strtolower($identifier)])->first();

            if ($adminUser) {
                if (empty($password)) {
                    return response()->json([
                        'message' => $lang === 'en' ? 'Password is required for Admin login.' : 'សូមបញ្ចូល Password សម្រាប់គណនី Admin'
                    ], 422);
                }

                if (!Hash::check($password, $adminUser->Password)) {
                    self::recordLoginAudit(
                        userId: $adminUser->AdminId,
                        username: $identifier,
                        role: $adminUser->Role,
                        displayName: $adminUser->name,
                        status: 'Failed',
                        details: 'Invalid password attempt for admin account',
                        request: $request
                    );
                    return response()->json([
                        'message' => $lang === 'en' ? 'Invalid password.' : 'ពាក្យសម្ងាត់មិនត្រឹមត្រូវ'
                    ], 422);
                }

                if ($adminUser->Status !== 'Active') {
                    return response()->json([
                        'message' => $lang === 'en' ? 'Account is suspended.' : 'គណនីនេះត្រូវបានផ្អាកជាបណ្ដោះអាសន្ន'
                    ], 403);
                }

                Auth::login($adminUser);

                $displayName = trim(($adminUser->FirstName ?? '') . ' ' . ($adminUser->LastName ?? '')) ?: $adminUser->Username;

                self::recordLoginAudit(
                    userId: $adminUser->AdminId,
                    username: $adminUser->Username,
                    role: $adminUser->Role,
                    displayName: $displayName,
                    status: 'Success',
                    details: "Admin logged in from IP {$request->ip()}",
                    request: $request
                );

                return response()->json([
                    'message' => 'Login successful.',
                    'role' => $adminUser->Role,
                    'user' => [
                        'id' => $adminUser->AdminId,
                        'name' => $displayName,
                        'username' => $adminUser->Username,
                        'email' => $adminUser->Username,
                        'role' => $adminUser->Role,
                        'status' => $adminUser->Status,
                        'profile_image' => $adminUser->ProfileImage,
                    ],
                    'redirect' => '/admin/dashboard',
                ]);
            }

            // 2. Student lookup strictly by StudentCode or StudentId in tblstudent
            $student = self::findStudentByIdentifier($identifier);

            if ($student) {
                Auth::login($student);

                $displayName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? '')) ?: ($student->StudentCode ?? ('Student #' . $student->StudentId));

                self::recordLoginAudit(
                    userId: $student->StudentId,
                    username: $student->StudentCode ?? (string)$student->StudentId,
                    role: 'Student',
                    displayName: $displayName,
                    status: 'Success',
                    details: "Student login with ID: " . ($student->StudentCode ?? $student->StudentId) . " from IP {$request->ip()}",
                    request: $request
                );

                return response()->json([
                    'message' => 'Login successful.',
                    'loginType' => 'student',
                    'role' => 'Student',
                    'user' => [
                        'id' => $student->StudentId,
                        'studentId' => $student->StudentCode ?? (string)$student->StudentId,
                        'name' => $displayName,
                        'role' => 'Student',
                        'status' => 'Active',
                        'profile_image' => $student->Photo,
                    ],
                    'redirect' => '/student',
                ]);
            }

            // Neither admin nor student found
            return response()->json([
                'message' => $lang === 'en' ? 'Student ID not found.' : 'រកមិនឃើញ Student ID នេះឡើយ'
            ], 422);

        } catch (\Throwable $e) {
            \Log::error('Login database exception: ' . $e->getMessage());
            return response()->json([
                'message' => $lang === 'en' ? 'Database connection error. Please try again.' : 'មានបញ្ហាតភ្ជាប់មូលដ្ឋានទិន្នន័យ សូមព្យាយាមម្តងទៀត'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            self::recordLoginAudit(
                userId: $user->id,
                username: $user->name,
                role: $user->role,
                displayName: $user->name,
                status: 'Logged Out',
                details: 'Session ended by user logout',
                request: $request
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user instanceof Student) {
            $student = $user->loadMissing(['skill', 'group']);
        } else {
            $student = Student::where('StudentId', $user->id ?? $user->AdminId)
                ->orWhere('UserId', $user->id ?? $user->AdminId)
                ->with(['skill', 'group'])
                ->first();
        }

        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email ?? $user->Username ?? $user->StudentCode ?? '',
                'role' => $user->role,
                'status' => $user->status,
                'profileImage' => $user->profile_image,
            ],
        ];

        if ($student) {
            $displayCode = $student->StudentCode ?: ('RTC-2026-' . str_pad((string)$student->StudentId, 5, '0', STR_PAD_LEFT));
            $payload['student'] = [
                'id' => $student->StudentId,
                'studentCode' => $displayCode,
                'name' => trim($student->FirstName . ' ' . $student->LastName),
                'firstName' => $student->FirstName,
                'lastName' => $student->LastName,
                'email' => $student->StudentCode ?? '',
                'phone' => $student->Phone,
                'skill' => $student->skill?->SkillName ?? '',
                'group' => $student->group?->GroupName ?? '',
                'shift' => $student->StudyShift,
                'enrolledMonth' => $student->EnrolledMonth,
                'enrolledYear' => $student->EnrolledYear,
                'photo' => $student->Photo ?? $user->profile_image,
                'profileImage' => $user->profile_image ?? $student->Photo,
                'telegramChatId' => $student->TelegramChatId,
                'telegramUsername' => $student->TelegramUsername,
                'telegramConnected' => !empty($student->TelegramChatId),
                'telegramConnectUrl' => 'https://t.me/onlinexam_bot?start=link_' . urlencode($student->StudentCode ?: $student->StudentId),
            ];

            $completedTestIds = DB::table('tblstudentsubmission')
                ->where('StudentId', $student->StudentId)
                ->whereNotNull('CompletedAt')
                ->pluck('TestId')
                ->toArray();

            $payload['tests'] = DB::table('tbltest as t')
                ->join('tblskill as sk', 't.SkillId', '=', 'sk.SkillId')
                ->where('sk.SkillName', $student->skill?->SkillName)
                ->where(function ($q) use ($student) {
                    $q->where('t.GroupId', $student->GroupId)
                        ->orWhereNull('t.GroupId');
                })
                ->where('t.Status', 'Published')
                ->whereNotIn('t.TestId', $completedTestIds)
                ->select(
                    't.TestId as id',
                    't.TestName as name',
                    'sk.SkillName as skill',
                    't.DurationMinutes as durationMinutes',
                    't.TotalMarks as totalMarks',
                    't.ScheduledAt as scheduledAt',
                    't.FinishedAt as finishedAt',
                    't.Status as status'
                )
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
                })
                ->filter(fn($t) => $t->status === 'Published')
                ->values();
        } else {
            $admin = ($user instanceof Admin) ? $user : Admin::find($user->id ?? $user->AdminId);
            if ($admin) {
                $fullName = trim($admin->FirstName . ' ' . $admin->LastName);
                if ($fullName) {
                    $payload['user']['name'] = $fullName;
                }
            }
        }

        $permsFile = storage_path('app/permissions.json');
        $permissions = [];
        if (file_exists($permsFile)) {
            $permissions = json_decode(file_get_contents($permsFile), true) ?: [];
        }
        $payload['permissions'] = $permissions;
        $payload['settings'] = AdminController::getSystemSettings();

        return response()->json($payload);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'shift' => ['nullable', 'string', 'max:50'],
        ]);

        $student = ($user instanceof Student) ? $user : Student::find($user->StudentId ?? $user->id);

        if ($student) {
            $studentUpdate = [
                'FirstName' => $data['firstName'],
                'LastName'  => $data['lastName'],
                'Phone'     => $data['phone'],
            ];
            if (!empty($data['shift'])) {
                $studentUpdate['StudyShift'] = $data['shift'];
            }
            $student->update($studentUpdate);

            return response()->json([
                'message' => 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ (Profile updated successfully).',
                'user' => [
                    'id' => $student->StudentId,
                    'name' => trim($student->FirstName . ' ' . $student->LastName),
                    'phone' => $student->Phone,
                    'shift' => $student->StudyShift,
                ]
            ]);
        } else {
            $admin = ($user instanceof Admin) ? $user : Admin::find($user->AdminId ?? $user->id);
            if ($admin) {
                $admin->update([
                    'FirstName' => $data['firstName'],
                    'LastName'  => $data['lastName'],
                    'Phone'     => $data['phone'],
                ]);
            }

            return response()->json([
                'message' => 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ (Profile updated successfully).',
                'user' => [
                    'id' => $admin ? $admin->AdminId : $user->id,
                    'name' => $admin ? trim($admin->FirstName . ' ' . $admin->LastName) : $user->name,
                    'phone' => $admin ? $admin->Phone : null,
                ]
            ]);
        }
    }

    public function verifyPhone(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'phone'    => ['required', 'string'],
        ]);

        $username   = trim($data['username']);
        $phoneInput = preg_replace('/[^0-9]/', '', $data['phone']);

        if (!$phoneInput) {
            return response()->json(['message' => 'សូមបញ្ចូលលេខទូរស័ព្ទឲ្យបានត្រឹមត្រូវ (Please enter a valid phone number).'], 422);
        }

        // Find Admin or Student
        $admin = Admin::whereRaw('LOWER(Username) = ?', [strtolower($username)])->first();
        $student = $admin ? null : self::findStudentByIdentifier($username);

        if (!$admin && !$student) {
            return response()->json(['message' => 'រកមិនឃើញឈ្មោះគណនីនេះក្នុងប្រព័ន្ធឡើយ (Account not found).'], 404);
        }

        $matched = false;
        $displayName = '';

        if ($student) {
            $studentPhone = preg_replace('/[^0-9]/', '', $student->Phone ?? '');
            if ($studentPhone && (str_ends_with($studentPhone, $phoneInput) || str_ends_with($phoneInput, $studentPhone))) {
                $matched = true;
                $displayName = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? '')) ?: $student->StudentCode;
            }
        }

        if ($admin) {
            $adminPhone = preg_replace('/[^0-9]/', '', $admin->Phone ?? '');
            if ($adminPhone && (str_ends_with($adminPhone, $phoneInput) || str_ends_with($phoneInput, $adminPhone))) {
                $matched = true;
                $displayName = trim(($admin->FirstName ?? '') . ' ' . ($admin->LastName ?? '')) ?: $admin->Username;
            }
        }

        if (!$matched) {
            return response()->json(['message' => 'លេខទូរស័ព្ទមិនត្រូវគ្នានឹងគណនីនេះឡើយ សូមពិនិត្យលេខទូរស័ព្ទដែលបានចុះឈ្មោះ (Phone number does not match registered profile).'], 422);
        }

        return response()->json([
            'message' => 'ការផ្ទៀងផ្ទាត់ជោគជ័យ! (Identity verified successfully)',
            'username' => $username,
            'displayName' => $displayName,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'phone'    => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $username   = trim($data['username']);
        $phoneInput = preg_replace('/[^0-9]/', '', $data['phone']);

        if (!$phoneInput) {
            return response()->json(['message' => 'សូមបញ្ចូលលេខទូរស័ព្ទឲ្យបានត្រឹមត្រូវ (Please enter a valid phone number).'], 422);
        }

        $admin = Admin::whereRaw('LOWER(Username) = ?', [strtolower($username)])->first();

        if ($admin) {
            $adminPhone = preg_replace('/[^0-9]/', '', $admin->Phone ?? '');
            if (!$adminPhone || (!str_ends_with($adminPhone, $phoneInput) && !str_ends_with($phoneInput, $adminPhone))) {
                return response()->json(['message' => 'លេខទូរស័ព្ទមិនត្រូវគ្នានឹងគណនីនេះឡើយ សូមពិនិត្យលេខទូរស័ព្ទដែលបានចុះឈ្មោះ (Phone number does not match registered profile).'], 422);
            }

            $admin->Password = Hash::make($data['password']);
            $admin->save();

            return response()->json([
                'message' => 'ពាក្យសម្ងាត់ត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ! (Password changed successfully)',
                'username' => $admin->Username,
                'redirect' => '/login',
            ]);
        }

        return response()->json(['message' => 'ការកំណត់ពាក្យសម្ងាត់ថ្មីអាចធ្វើបានសម្រាប់ Admin តែប៉ុណ្ណោះ (Password reset is for Admin only).'], 422);
    }

    public function forgotPassword(Request $request)
    {
        return $this->resetPassword($request);
    }
    public function uploadProfileImage(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // Max 5MB
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $sourcePath = $file->getRealPath();

            $image = null;
            if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
                $rawContents = file_get_contents($sourcePath);
                $image = @imagecreatefromstring($rawContents);
            }

            $finalImageUrl = null;

            if ($image) {
                // Get original dimensions
                $width = imagesx($image);
                $height = imagesy($image);
                $maxDim = 400; // Resize to max 400px for crisp, super-fast avatar load

                if ($width > $maxDim || $height > $maxDim) {
                    $ratio = $width / $height;
                    if ($ratio > 1) {
                        $newWidth = $maxDim;
                        $newHeight = (int) round($maxDim / $ratio);
                    } else {
                        $newWidth = (int) round($maxDim * $ratio);
                        $newHeight = $maxDim;
                    }

                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    $white = imagecolorallocate($newImage, 255, 255, 255);
                    imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $white);
                    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $newImage;
                }

                // Compress to lightweight JPEG in memory (78% quality ~ 15KB-30KB)
                ob_start();
                imagejpeg($image, null, 78);
                $compressedBinary = ob_get_clean();
                imagedestroy($image);

                $finalImageUrl = 'data:image/jpeg;base64,' . base64_encode($compressedBinary);
            } else {
                // Fallback: encode original contents directly to data URL
                $mime = $file->getMimeType() ?: 'image/jpeg';
                $finalImageUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($sourcePath));
            }

            // Save to model
            if ($user instanceof Student) {
                $user->Photo = $finalImageUrl;
                $user->save();
            } elseif ($user instanceof Admin) {
                $user->ProfileImage = $finalImageUrl;
                $user->save();
            } else {
                $user->profile_image = $finalImageUrl;
                if (isset($user->Photo)) $user->Photo = $finalImageUrl;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'រូបថតប្រវត្តិរូបត្រូវបានផ្លាស់ប្តូរដោយជោគជ័យ (Profile photo updated successfully).',
                'profileImage' => $finalImageUrl,
            ]);
        }

        return response()->json(['message' => 'No image provided.'], 400);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user)
            return response()->json(['message' => 'Unauthenticated.'], 401);

        $request->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->password = $request->newPassword;
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    private function generateStudentCode(?string $year = null): string
    {
        $yearStr = !empty($year) ? trim($year) : date('Y');
        for ($i = 0; $i < 100; $i++) {
            $randomNum = str_pad((string)mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            $candidateCode = 'RTC-' . $yearStr . '-' . $randomNum;
            $exists = Student::where('StudentCode', $candidateCode)->exists();
            if (!$exists) {
                return $candidateCode;
            }
        }
        $nextId = (Student::max('StudentId') ?? 0) + 1;
        return 'RTC-' . $yearStr . '-' . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
    }

    private function processUploadedPhoto($photoInput, $uploadedFile = null): ?string
    {
        if (empty($photoInput) && empty($uploadedFile)) {
            return null;
        }

        try {
            $uploadDir = public_path('uploads/profiles');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
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
                    @file_put_contents($uploadDir . '/' . $filename, $data);
                    return '/uploads/profiles/' . $filename;
                }
            }

            if (!empty($photoInput) && is_string($photoInput) && str_starts_with($photoInput, '/uploads/')) {
                return $photoInput;
            }
        } catch (\Throwable $e) {
            \Log::warning('processUploadedPhoto notice: ' . $e->getMessage());
        }

        return null;
    }

    private function parseDurationMonths($input): int
    {
        if (empty($input)) return 4;
        if (is_numeric($input)) return (int)$input;
        $str = (string)$input;
        if (preg_match('/(\d+)\s*(ឆ្នាំ|year)/iu', $str, $m)) {
            return (int)$m[1] * 12;
        }
        if (preg_match('/(\d+)/', $str, $m)) {
            return (int)$m[1];
        }
        return 4;
    }

    public static function findStudentByIdentifier(?string $identifier): ?Student
    {
        $id = trim($identifier ?? '');
        if ($id === '') return null;

        // 1. Direct exact match (case-insensitive)
        $student = Student::with(['skill', 'group'])
            ->whereRaw('LOWER(StudentCode) = ?', [strtolower($id)])
            ->first();

        if ($student) return $student;

        // 2. Direct numeric match for StudentId
        if (is_numeric($id)) {
            $student = Student::with(['skill', 'group'])->find((int)$id);
            if ($student) return $student;
        }

        // 3. Flexible match for RTC-YYYY-XXXXX (handling varying zero padding: e.g. 0002 vs 00002)
        if (preg_match('/^RTC-(\d{4})-(\d+)$/i', $id, $matches)) {
            $year = $matches[1];
            $num = (int)$matches[2];

            $variations = [
                'RTC-' . $year . '-' . $num,
                'RTC-' . $year . '-' . str_pad((string)$num, 4, '0', STR_PAD_LEFT),
                'RTC-' . $year . '-' . str_pad((string)$num, 5, '0', STR_PAD_LEFT),
                'RTC-' . $year . '-' . str_pad((string)$num, 6, '0', STR_PAD_LEFT),
            ];

            $student = Student::with(['skill', 'group'])
                ->whereIn('StudentCode', $variations)
                ->orWhere('StudentId', $num)
                ->first();

            if ($student) return $student;
        }

        return null;
    }
}
