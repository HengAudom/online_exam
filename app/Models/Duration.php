<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Duration extends Model
{
    use HasFactory;

    protected $table = 'tblduration';
    protected $primaryKey = 'DurationId';

    protected $fillable = [
        'DurationName',
        'DurationMonths',
    ];
}
