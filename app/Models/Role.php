<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'is_system',
        'scope',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions'
        )->withTimestamps();
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_roles'
        )->withPivot([
            'company_id',
            'status',
            'assigned_at',
        ])->withTimestamps();
    }
}