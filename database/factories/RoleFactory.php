<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'status' => 'activo',
            'is_system' => false,
            'scope' => 'company',
        ];
    }

    public function system(): static
    {
        return $this->state([
            'is_system' => true,
        ]);
    }

    public function global(): static
    {
        return $this->state([
            'scope' => 'global',
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => 'inactivo',
        ]);
    }
}