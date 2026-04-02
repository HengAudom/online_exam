<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'tblQuestion';
    protected $primaryKey = 'QuestionId';

    protected $fillable = [
        'TestId',
        'QuestionText',
        'Points',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class, 'TestId', 'TestId');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'QuestionId', 'QuestionId');
    }

    public function submissionDetails()
    {
        return $this->hasMany(SubmissionDetail::class, 'QuestionId', 'QuestionId');
    }
}
