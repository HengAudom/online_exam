<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    protected $table = 'tblanswer';
    protected $primaryKey = 'AnswerId';

    protected $fillable = [
        'QuestionId',
        'AnswerText',
        'IsCorrect',
    ];

    protected $casts = [
        'IsCorrect' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'QuestionId', 'QuestionId');
    }
}
