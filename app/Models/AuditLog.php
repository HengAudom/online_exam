<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'tblauditlog';
    protected $primaryKey = 'AuditLogId';
    public $timestamps = false;

    protected $fillable = [
        'UserId',
        'UserName',
        'UserRole',
        'Action',
        'Module',
        'Target',
        'Status',
        'Details',
        'IpAddress',
        'UserAgent',
        'CreatedAt',
    ];

    protected $casts = [
        'CreatedAt' => 'datetime',
    ];
}
