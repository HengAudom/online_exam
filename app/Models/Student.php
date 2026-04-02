<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'tblStudent';
    protected $primaryKey = 'StudentId';

    protected $fillable = [
        'UserId',
        'SkillId',
        'BatchId',
        'FirstName',
        'LastName',
        'Gender',
        'StudyShift',
        'Phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserId', 'id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'SkillId', 'SkillId');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'BatchId', 'BatchId');
    }

    public function submissions()
    {
        return $this->hasMany(StudentSubmission::class, 'StudentId', 'StudentId');
    }
}
