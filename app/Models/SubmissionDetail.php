<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionDetail extends Model
{
    protected $table = 'tblsubmissiondetail';
    protected $primaryKey = 'DetailId';

    protected $fillable = [
        'SubmissionId',
        'QuestionId',
        'SelectedAnswerId',
        'IsCorrect',
    ];

    protected $casts = [
        'IsCorrect' => 'boolean',
    ];

    public function submission()
    {
        return $this->belongsTo(StudentSubmission::class, 'SubmissionId', 'SubmissionId');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'QuestionId', 'QuestionId');
    }

    public function selectedAnswer()
    {
        return $this->belongsTo(Answer::class, 'SelectedAnswerId', 'AnswerId');
    }
}
