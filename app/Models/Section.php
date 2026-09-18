<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'module_id',
        'code',
        'name',
        'description',
        'status',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'module_id' => 'integer',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
