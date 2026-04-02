<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Skill;
use App\Models\Student;
use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $studentCount   = Student::count();
        $testCount      = DB::table('tblTest')->count();
        $completedCount = DB::table('tblStudentSubmission')->whereNotNull('CompletedAt')->count();
        $avgScore       = DB::table('tblStudentSubmission')
            ->whereNotNull('CompletedAt')
            ->avg('Score') ?? 0;

        return response()->json([
            'activeStudents'  => $studentCount,
            'publishedTests'  => $testCount,
            'completedExams'  => $completedCount,
            'avgScore'        => round($avgScore, 1),
            'latestActivity'  => DB::table('tblStudentSubmission as ss')
                ->join('tblStudent as s', 'ss.StudentId', '=', 's.StudentId')
                ->join('tblTest as t', 'ss.TestId', '=', 't.TestId')
                ->whereNotNull('ss.CompletedAt')
                ->orderBy('ss.CompletedAt', 'desc')
                ->limit(5)
                ->select('s.FirstName', 's.LastName', 't.TestName', 'ss.Score', 'ss.CompletedAt')
                ->get()
                ->map(fn($r) => [
                    'title'       => "{$r->FirstName} {$r->LastName} completed {$r->TestName}",
                    'description' => "Score: {$r->Score} · " . date('M d, Y', strtotime($r->CompletedAt)),
                ]),
        ]);
    }

    public function students(Request $request)
    {
        $users = DB::table('users as u')
            ->leftJoin('tblStudent as s', 'u.id', '=', 's.UserId')
            ->leftJoin('tblSkill as sk', 's.SkillId', '=', 'sk.SkillId')
            ->leftJoin('tblBatch as b', 's.BatchId', '=', 'b.BatchId')
            ->leftJoin('tblAdminProfile as a', 'u.id', '=', 'a.UserId')
            ->select(
                'u.id as userId',
                'u.name as username',
                'u.email as email',
                'u.role as role',
                'u.status as status',
                's.StudentId as studentId',
                's.FirstName as s_first_name',
                's.LastName as s_last_name',
                's.Phone as s_phone',
                's.Gender as gender',
                's.StudyShift as shift',
                'sk.SkillName as skill',
                'b.BatchName as batch',
                'a.AdminProfileId as adminId',
                'a.FirstName as a_first_name',
                'a.LastName as a_last_name',
                'a.Phone as a_phone'
            )
            ->orderBy('u.id', 'desc')
            ->get();

        $formatted = $users->map(function ($u) {
            $isStudent = $u->role === 'Student';
            return [
                'id'         => $u->userId,
                'name'       => trim($isStudent ? ($u->s_first_name . ' ' . $u->s_last_name) : ($u->a_first_name . ' ' . $u->a_last_name)) ?: $u->username,
                'first_name' => $isStudent ? $u->s_first_name : $u->a_first_name,
                'last_name'  => $isStudent ? $u->s_last_name : $u->a_last_name,
                'email'      => $u->email,
                'username'   => $u->username,
                'phone'      => $isStudent ? $u->s_phone : $u->a_phone,
                'role'       => $u->role,
                'status'     => $u->status,
                'gender'     => $u->gender,
                'shift'      => $u->shift,
                'skill'      => $u->skill,
                'batch'      => $u->batch,
            ];
        });

        return response()->json(['students' => $formatted]);
    }

    public function skillsBatches(Request $request)
    {
        $skills  = Skill::orderBy('SkillName')->get(['SkillId', 'SkillName', 'Description']);
        $batches = Batch::orderBy('BatchName')->get(['BatchId', 'BatchName', 'StartDate', 'EndDate']);

        return response()->json([
            'skills'  => $skills,
            'batches' => $batches,
        ]);
    }

    public function addSkill(Request $request)
    {
        $data  = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $skill = Skill::create(['SkillName' => $data['name'], 'Description' => '']);
        return response()->json(['skill' => ['SkillId' => $skill->SkillId, 'SkillName' => $skill->SkillName]], 201);
    }

    public function deleteSkill(Request $request, $id)
    {
        Skill::findOrFail($id)->delete();
        return response()->json(['message' => 'Skill deleted.']);
    }

    public function updateSkill(Request $request, $id)
    {
        $data  = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $skill = Skill::findOrFail($id);
        $skill->update(['SkillName' => $data['name']]);
        return response()->json(['skill' => ['SkillId' => $skill->SkillId, 'SkillName' => $skill->SkillName]]);
    }

    public function addBatch(Request $request)
    {
        $data  = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate'   => ['required', 'date'],
        ]);
        $batch = Batch::create([
            'BatchName' => $data['name'],
            'StartDate' => $data['startDate'],
            'EndDate'   => $data['endDate'],
        ]);
        return response()->json(['batch' => [
            'BatchId'   => $batch->BatchId,
            'BatchName' => $batch->BatchName,
            'StartDate' => $batch->StartDate,
            'EndDate'   => $batch->EndDate,
        ]], 201);
    }

    public function deleteBatch(Request $request, $id)
    {
        Batch::findOrFail($id)->delete();
        return response()->json(['message' => 'Batch deleted.']);
    }

    public function updateBatch(Request $request, $id)
    {
        $data  = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate'   => ['required', 'date'],
        ]);
        $batch = Batch::findOrFail($id);
        $batch->update([
            'BatchName' => $data['name'],
            'StartDate' => $data['startDate'],
            'EndDate'   => $data['endDate'],
        ]);
        return response()->json(['batch' => [
            'BatchId'   => $batch->BatchId,
            'BatchName' => $batch->BatchName,
            'StartDate' => $batch->StartDate,
            'EndDate'   => $batch->EndDate,
        ]]);
    }

    public function tests(Request $request)
    {
        $tests = DB::table('tblTest as t')
            ->join('tblSkill as sk', 't.SkillId', '=', 'sk.SkillId')
            ->join('users as u', 't.CreatedByUserId', '=', 'u.id')
            ->leftJoin('tblBatch as b', 't.BatchId', '=', 'b.BatchId')
            ->select(
                't.TestId as id',
                't.TestName as name',
                't.SkillId as skillId',
                't.BatchId as batchId',
                'sk.SkillName as skill',
                'b.BatchName as batch',
                't.DurationMinutes as durationMinutes',
                't.TotalMarks as totalMarks',
                't.Status as status',
                't.ScheduledAt as scheduledAt',
                't.FinishedAt as finishedAt',
                'u.name as createdBy',
                DB::raw('(SELECT COUNT(*) FROM tblQuestion WHERE tblQuestion.TestId = t.TestId) as questionCount')
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
    }

    public function results(Request $request)
    {
        $results = DB::table('tblStudentSubmission as ss')
            ->join('tblStudent as s', 'ss.StudentId', '=', 's.StudentId')
            ->join('tblTest as t', 'ss.TestId', '=', 't.TestId')
            ->whereNotNull('ss.CompletedAt')
            ->select(
                'ss.SubmissionId as id',
                DB::raw("CONCAT(s.FirstName, ' ', s.LastName) as studentName"),
                't.TestName as testName',
                't.TotalMarks as totalMarks',
                'ss.TotalCorrect as totalCorrect',
                'ss.Score as score',
                'ss.CompletedAt as completedAt'
            )
            ->orderBy('ss.CompletedAt', 'desc')
            ->get()
            ->map(function ($r) {
                $accuracy = $r->totalMarks > 0
                    ? round(($r->score / $r->totalMarks) * 100, 1)
                    : 0;
                return array_merge((array) $r, ['accuracy' => $accuracy]);
            });

        return response()->json(['results' => $results]);
    }

    public function deleteSubmission($id)
    {
        DB::table('tblStudentSubmission')->where('SubmissionId', $id)->delete();
        return response()->json(['message' => 'Result deleted successfully']);
    }

    public function updateStudent(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $currentUser = auth()->user();
        $isTargetSuperAdmin = in_array($user->role, ['Super Admin', 'SuperAdmin']);
        $isSelectingSuperAdmin = in_array($request->role, ['Super Admin', 'SuperAdmin']);
        $isCurrentSuperAdmin = $currentUser && in_array($currentUser->role, ['Super Admin', 'SuperAdmin']);

        if (($isSelectingSuperAdmin || $isTargetSuperAdmin) && !$isCurrentSuperAdmin) {
            return response()->json(['message' => 'Unauthorized. Only Super Admins can manage other Super Admins.'], 403);
        }

        $isStudent = $user->role === 'Student';

        $rules = [
            'firstName'   => ['required', 'string', 'max:255'],
            'lastName'    => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255'],
            'phone'       => ['required', 'string', 'max:50'],
            'newPassword' => ['nullable', 'string', 'min:6'],
            'role'        => ['nullable', 'string', 'in:Student,Admin,Super Admin,SuperAdmin'],
        ];

        if ($isStudent) {
            $rules['shift'] = ['required', 'string', 'max:50'];
            $rules['skill'] = ['required', 'string', 'max:255'];
            $rules['batch'] = ['required', 'string', 'max:255'];
        }

        $data = $request->validate($rules);

        $userUpdate = ['email' => $data['email']];
        if (!empty($data['newPassword'])) {
            $userUpdate['password'] = \Illuminate\Support\Facades\Hash::make($data['newPassword']);
        }
        $user->update($userUpdate);

        if ($isStudent) {
            $skill = Skill::firstOrCreate(['SkillName' => $data['skill']], ['Description' => '']);
            $batch = Batch::firstOrCreate(
                ['BatchName' => $data['batch']],
                ['StartDate' => now()->toDateString(), 'EndDate' => now()->addMonths(3)->toDateString()]
            );
            $student = Student::where('UserId', $id)->first();
            if ($student) {
                $student->update([
                    'FirstName'  => $data['firstName'],
                    'LastName'   => $data['lastName'],
                    'Phone'      => $data['phone'],
                    'StudyShift' => $data['shift'],
                    'SkillId'    => $skill->SkillId,
                    'BatchId'    => $batch->BatchId,
                ]);
            }
        } else {
            $admin = AdminProfile::where('UserId', $id)->first();
            if ($admin) {
                $admin->update([
                    'FirstName' => $data['firstName'],
                    'LastName'  => $data['lastName'],
                    'Phone'     => $data['phone'],
                ]);
            }
        }

        return response()->json(['message' => 'User updated.', 'student' => array_merge(
            ['id' => $user->id, 'name' => $data['firstName'] . ' ' . $data['lastName'], 'email' => $data['email'], 'phone' => $data['phone'], 'role' => $user->role],
            $isStudent ? ['skill' => $data['skill'], 'batch' => $data['batch'], 'shift' => $data['shift']] : []
        )]);
    }

    public function deleteStudent(Request $request, $id)
    {
        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $currentUser = auth()->user();
        $isTargetSuperAdmin = in_array($user->role, ['Super Admin', 'SuperAdmin']);
        $isCurrentSuperAdmin = $currentUser && in_array($currentUser->role, ['Super Admin', 'SuperAdmin']);

        if ($isTargetSuperAdmin && !$isCurrentSuperAdmin) {
            return response()->json(['message' => 'Unauthorized. Only Super Admins can delete other Super Admins.'], 403);
        }

        if ($user->role === 'Student') {
            Student::where('UserId', $id)->delete();
        } else {
            AdminProfile::where('UserId', $id)->delete();
        }
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function addStudent(Request $request)
    {
        $currentUser = auth()->user();
        $isSelectingSuperAdmin = in_array($request->input('role'), ['Super Admin', 'SuperAdmin']);
        $isCurrentSuperAdmin = $currentUser && in_array($currentUser->role, ['Super Admin', 'SuperAdmin']);

        if ($isSelectingSuperAdmin && !$isCurrentSuperAdmin) {
            return response()->json(['message' => 'Unauthorized. Only Super Admins can create new Super Admins.'], 403);
        }

        $isStudent = $request->input('role', 'Student') === 'Student';

        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'lastName'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'unique:users,email'],
            'username'  => ['required', 'string', 'unique:users,name'],
            'password'  => ['required', 'string', 'min:6'],
            'phone'     => ['required', 'string', 'max:50'],
            'role'      => ['required', 'string', 'in:Student,Admin,Super Admin,SuperAdmin'],
        ];

        if ($isStudent) {
            $rules['gender']  = ['required', 'string'];
            $rules['shift']   = ['required', 'string'];
            $rules['skillId'] = ['required', 'integer'];
            $rules['batchId'] = ['required', 'integer'];
        }

        $data = $request->validate($rules);
        $role = $request->input('role', 'Student');

        $user = User::create([
            'name'     => $data['username'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $role,
            'status'   => 'Active',
        ]);

        if ($isStudent) {
            Student::create([
                'UserId'     => $user->id,
                'SkillId'    => $data['skillId'],
                'BatchId'    => $data['batchId'],
                'FirstName'  => $data['firstName'],
                'LastName'   => $data['lastName'],
                'Gender'     => $data['gender'],
                'StudyShift' => $data['shift'],
                'Phone'      => $data['phone'],
            ]);
        } else {
            AdminProfile::create([
                'UserId'          => $user->id,
                'CreatedByUserId' => auth()->id() ?? User::where('role', 'Super Admin')->first()->id ?? 1,
                'FirstName'       => $data['firstName'],
                'LastName'        => $data['lastName'],
                'Phone'           => $data['phone'],
            ]);
        }

        return response()->json(['message' => "$role created."]);
    }
}
