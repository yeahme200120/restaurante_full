<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id',
        'company_id',
        'branch_id',
        'module',
        'action',
        'record_type',
        'record_id',
        'old_values',
        'new_values',
        'result',
        'error_code',
        'error_message',
        'event_id',
        'idempotency_key',
        'request_id',
        'ip',
        'user_agent',
        'device',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'role_id' => 'integer',
            'company_id' => 'integer',
            'branch_id' => 'integer',
            'record_id' => 'integer',
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
