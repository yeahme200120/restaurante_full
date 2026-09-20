<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'is_core',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }
    public function companyModules()
    {
        return $this->hasMany(CompanyModule::class);
    }

    public function companies()
    {
        return $this->belongsToMany(
            Company::class,
            'company_modules'
        )->withPivot([
            'status',
            'enabled_at',
            'disabled_at',
            'metadata',
        ])->withTimestamps();
    }
}
