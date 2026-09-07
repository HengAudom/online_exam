<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $table = 'tblgroup';
    protected $primaryKey = 'GroupId';
    public $timestamps = true;

    protected $fillable = [
        'GroupName',
        'StartDate',
        'EndDate'
    ];
}

