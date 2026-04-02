<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminProfile extends Model
{
    protected $table = 'tblAdminProfile';
    protected $primaryKey = 'AdminProfileId';

    protected $fillable = [
        'UserId',
        'CreatedByUserId',
        'FirstName',
        'LastName',
        'Phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'UserId', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'CreatedByUserId', 'id');
    }
}
