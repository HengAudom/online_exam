<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'tblquestion';
    protected $primaryKey = 'QuestionId';

    protected $fillable = [
        'TestId',
        'QuestionText',
        'Passage',
        'IsExample',
        'Points',
    ];

    protected $casts = [
        'IsExample' => 'boolean',
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
