<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $module = fake()->slug(1);
        $action = fake()->randomElement([
            'view',
            'create',
            'update',
            'delete',
        ]);

        return [
            'code' => "{$module}.{$action}",
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'module' => $module,
            'section' => null,
            'action' => $action,
            'status' => 'activo',
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => 'inactivo',
        ]);
    }
}