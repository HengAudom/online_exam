<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $table = 'tblTest';
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
        'BatchId',
    ];

    protected $casts = [
        'ScheduledAt' => 'datetime',
        'FinishedAt'  => 'datetime',
    ];

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'SkillId', 'SkillId');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'BatchId', 'BatchId');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'CreatedByUserId', 'id');
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
