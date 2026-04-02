<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    use HasFactory;

    protected $table = 'tblBatch';
    protected $primaryKey = 'BatchId';

    protected $fillable = [
        'BatchName',
        'StartDate',
        'EndDate'
    ];
}
