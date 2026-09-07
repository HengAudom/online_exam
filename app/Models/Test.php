<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $table = 'tbltest';
    protected $primaryKey = 'TestId';

    protected $fillable = [
        'SkillId',
        'CreatedByUserId',
        'TestName',
        'DurationMinutes',
        'TotalMarks',
        'ScheduledAt',
        'FinishedAt',
        'Status',
        'GroupId',
    ];

    protected $casts = [
        'ScheduledAt' => 'datetime',
        'FinishedAt' => 'datetime',
    ];

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'SkillId', 'SkillId');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'GroupId', 'GroupId');
    }

    public function createdBy()
    {
        return $this->belongsTo(Admin::class, 'CreatedByUserId', 'AdminId');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'TestId', 'TestId');
    }

    public function submissions()
    {
        return $this->hasMany(StudentSubmission::class, 'TestId', 'TestId');
    }
}
