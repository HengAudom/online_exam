<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'tbladmin';
    protected $primaryKey = 'AdminId';

    protected $fillable = [
        'Username',
        'Password',
        'Role',
        'Status',
        'FirstName',
        'LastName',
        'Phone',
        'ProfileImage',
        'CreatedByUserId',
    ];

    protected $hidden = [
        'Password',
        'remember_token',
    ];

    public function getAuthIdentifier()
    {
        return 'admin:' . $this->AdminId;
    }

    public function getAuthPassword()
    {
        return $this->Password;
    }

    public function getAuthIdentifierName()
    {
        return 'AdminId';
    }

    // Accessors for compatibility
    public function getIdAttribute()
    {
        return $this->AdminId;
    }

    public function getNameAttribute()
    {
        $full = trim(($this->FirstName ?? '') . ' ' . ($this->LastName ?? ''));
        return $full ?: ($this->Username ?? 'Admin');
    }

    public function getRoleAttribute()
    {
        return $this->attributes['Role'] ?? 'Admin';
    }

    public function getStatusAttribute()
    {
        return $this->attributes['Status'] ?? 'Active';
    }

    public function getProfileImageAttribute()
    {
        return $this->attributes['ProfileImage'] ?? null;
    }

    public function setProfileImageAttribute($value)
    {
        $this->attributes['ProfileImage'] = $value;
    }
}
