<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'module_id',
        'status',
        'enabled_at',
        'disabled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}