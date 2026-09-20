<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'role_id' => null,
            'company_id' => null,
            'branch_id' => null,
            'module' => fake()->randomElement([
                'auth',
                'companies',
                'licenses',
                'users',
                'modules',
                'sections',
            ]),
            'action' => fake()->randomElement([
                'created',
                'updated',
                'deleted',
                'activated',
                'deactivated',
                'login',
                'logout',
            ]),
            'record_type' => null,
            'record_id' => null,
            'old_values' => null,
            'new_values' => null,
            'result' => 'success',
            'error_code' => null,
            'error_message' => null,
            'event_id' => null,
            'idempotency_key' => null,
            'request_id' => fake()->uuid(),
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device' => fake()->randomElement([
                'web',
                'flutter',
                'api',
                'system',
            ]),
            'created_at' => now(),
        ];
    }
}
