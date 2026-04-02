<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Skill;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,name'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
            'gender' => ['required', 'string', 'max:50'],
            'shift' => ['required', 'string', 'max:50'],
            'skill' => ['required', 'string', 'max:255'],
            'batch' => ['required', 'string', 'max:255'],
        ]);

        $skill = Skill::firstOrCreate(
            ['SkillName' => $data['skill']],
            ['Description' => '']
        );

        $batch = Batch::firstOrCreate(
            ['BatchName' => $data['batch']],
            [
                'StartDate' => now()->toDateString(),
                'EndDate' => now()->addMonths(3)->toDateString(),
            ]
        );

        $user = User::create([
            'name' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'Student',
            'status' => 'Active',
        ]);

        Student::create([
            'UserId' => $user->id,
            'SkillId' => $skill->SkillId,
            'BatchId' => $batch->BatchId,
            'FirstName' => $data['firstName'],
            'LastName' => $data['lastName'],
            'Gender' => $data['gender'],
            'StudyShift' => $data['shift'],
            'Phone' => $data['phone'],
        ]);

        return response()->json(['message' => 'Registration successful. Please log in.'], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('name', $data['username'])
            ->orWhere('email', $data['username'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        Auth::login($user);

        return response()->json([
            'message' => 'Login successful.',
            'role' => $user->role,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        return response()->json(['message' => 'Logged out.']);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $student = Student::where('UserId', $user->id)->with(['skill', 'batch'])->first();

        $payload = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'profileImage' => $user->profile_image,
            ],
        ];

        if ($student) {
            $payload['student'] = [
                'id' => $student->StudentId,
                'name' => $student->FirstName . ' ' . $student->LastName,
                'email' => $user->email,
                'phone' => $student->Phone,
                'skill' => $student->skill?->SkillName ?? '',
                'batch' => $student->batch?->BatchName ?? '',
                'shift' => $student->StudyShift,
            ];

            $payload['tests'] = DB::table('tblTest as t')
                ->join('tblSkill as sk', 't.SkillId', '=', 'sk.SkillId')
                ->where('sk.SkillName', $student->skill?->SkillName)
                ->where(function($q) use ($student) {
                    $q->where('t.BatchId', $student->BatchId)
                      ->orWhereNull('t.BatchId');
                })
                ->where('t.Status', 'Published')
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
                });
        } else {
            $admin = \App\Models\AdminProfile::where('UserId', $user->id)->first();
            if ($admin) {
                $fullName = trim($admin->FirstName . ' ' . $admin->LastName);
                if ($fullName) {
                    $payload['user']['name'] = $fullName;
                }
            }
        }

        return response()->json($payload);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:50'],
            'shift' => ['required', 'string', 'max:50'],
        ]);

        $student = Student::where('UserId', $user->id)->first();

        $user->email = $data['email'];
        $user->save();

        if ($student) {
            $student->FirstName = $data['firstName'];
            $student->LastName = $data['lastName'];
            $student->Phone = $data['phone'];
            $student->StudyShift = $data['shift'];
            $student->save();
        }

        return response()->json([
            'message' => 'Profile updated successfully.',
            'student' => [
                'id' => $student?->StudentId,
                'name' => $student ? $student->FirstName . ' ' . $student->LastName : '',
                'email' => $user->email,
                'phone' => $student?->Phone,
                'skill' => $student?->skill?->SkillName ?? '',
                'batch' => $student?->batch?->BatchName ?? '',
                'shift' => $student?->StudyShift ?? '',
            ],
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'Email not found. Please check your spelling.'], 404);
        }

        $otp = rand(100000, 999999);
        
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $data['email']],
            ['token' => $otp, 'created_at' => now()]
        );

        try {
            \Illuminate\Support\Facades\Mail::raw("Your password reset OTP code is: {$otp}\n\nIf you did not request a password reset, please ignore this email.", function ($message) use ($user) {
                $message->to($user->email)->subject('Password Reset OTP');
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mail sending failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'An OTP code has been sent to your email address.']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->where('token', $data['otp'])
            ->first();

        if (! $record) {
            return response()->json(['message' => 'Invalid or expired OTP code.'], 404);
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->password = $data['password'];
        $user->save();

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return response()->json(['message' => 'Password updated successfully.']);
    }
    public function uploadProfileImage(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // Increased to 5MB
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = time() . '_' . $user->id . '.jpg'; // Always save as jpg for consistent compression
            $destinationPath = public_path('uploads/profiles');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $sourcePath = $file->getRealPath();
            $targetPath = $destinationPath . '/' . $filename;

            // Load image based on extension
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    $image = imagecreatefromjpeg($sourcePath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($sourcePath);
                    imagepalettetotruecolor($image); // Handle transparency
                    break;
                case 'webp':
                    $image = imagecreatefromwebp($sourcePath);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($sourcePath);
                    break;
                default:
                    return response()->json(['message' => 'Unsupported image format.'], 400);
            }

            if (!$image) {
                return response()->json(['message' => 'Failed to process image.'], 500);
            }

            // Get original dimensions
            $width = imagesx($image);
            $height = imagesy($image);
            $maxDim = 800; // Resize to max 800px

            if ($width > $maxDim || $height > $maxDim) {
                $ratio = $width / $height;
                if ($ratio > 1) {
                    $newWidth = $maxDim;
                    $newHeight = $maxDim / $ratio;
                } else {
                    $newWidth = $maxDim * $ratio;
                    $newHeight = $maxDim;
                }
                
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $newImage;
            }

            // Save as compressed JPEG (80% quality)
            imagejpeg($image, $targetPath, 80);
            imagedestroy($image);

            // Delete old profile image if it exists and is different
            if ($user->profile_image) {
                $oldFileRelative = ltrim($user->profile_image, '/');
                $oldFilePath = public_path($oldFileRelative);
                if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                    @unlink($oldFilePath); // Silent delete
                }
            }

            $user->profile_image = '/uploads/profiles/' . $filename;
            $user->save();

            return response()->json([
                'message' => 'Profile image uploaded and optimized.',
                'profileImage' => $user->profile_image
            ]);
        }

        return response()->json(['message' => 'No image provided.'], 400);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated.'], 401);

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
}
