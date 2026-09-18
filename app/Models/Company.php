<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'email',
        'phone',
        'logo_path',
        'status',
        'timezone',
        'locale',
        'notes',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function licenses()
    {
        return $this->hasMany(CompanyLicense::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_company_assignments'
        )->withPivot([
            'is_default',
            'status',
        ])->withTimestamps();
    }
}
