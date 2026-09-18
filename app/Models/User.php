<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'user_company_assignments'
        )->withPivot([
            'is_default',
            'status',
        ])->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(
            Branch::class,
            'user_branch_assignments'
        )->withPivot([
            'is_default',
            'status',
        ])->withTimestamps();
    }
}
