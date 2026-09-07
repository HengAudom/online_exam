<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Student extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'tblstudent';
    protected $primaryKey = 'StudentId';

    protected $fillable = [
        'StudentCode',
        'UserId',
        'SkillId',
        'GroupId',
        'FirstName',
        'LastName',
        'Gender',
        'StudyShift',
        'EnrolledMonth',
        'EnrolledYear',
        'DurationMonths',
        'Phone',
        'Photo',
        'TelegramChatId',
        'TelegramUsername',
    ];

    public function getAuthIdentifier()
    {
        return 'student:' . $this->StudentId;
    }

    public function getAuthIdentifierName()
    {
        return 'StudentId';
    }

    public function getIdAttribute()
    {
        return $this->StudentId;
    }

    public function getNameAttribute()
    {
        $full = trim(($this->FirstName ?? '') . ' ' . ($this->LastName ?? ''));
        return $full ?: ($this->StudentCode ?? ('Student #' . $this->StudentId));
    }

    public function getRoleAttribute()
    {
        return 'Student';
    }

    public function getStatusAttribute()
    {
        return 'Active';
    }

    public function getProfileImageAttribute()
    {
        return $this->attributes['Photo'] ?? null;
    }

    public function setProfileImageAttribute($value)
    {
        $this->attributes['Photo'] = $value;
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'SkillId', 'SkillId');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'GroupId', 'GroupId');
    }

    public function submissions()
    {
        return $this->hasMany(StudentSubmission::class, 'StudentId', 'StudentId');
    }

    protected static function booted()
    {
        static::saved(function ($student) {
            if (!empty($student->StudentCode)) {
                $name = trim(($student->FirstName ?? '') . ' ' . ($student->LastName ?? ''));
                try {
                    $gasUrl = config('services.telegram.google_script_url', env('TELEGRAM_GOOGLE_SCRIPT_URL'));
                    if (!empty($gasUrl)) {
                        \Illuminate\Support\Facades\Http::timeout(3)->post($gasUrl, [
                            'action' => 'sync_roster',
                            'students' => [
                                [
                                    'code' => $student->StudentCode,
                                    'name' => $name ?: 'Student #' . $student->StudentId,
                                    'chatId' => $student->TelegramChatId ?? '',
                                    'phone' => $student->Phone ?? '',
                                ]
                            ]
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Telegram auto-sync student error: ' . $e->getMessage());
                }
            }
        });
    }
}
