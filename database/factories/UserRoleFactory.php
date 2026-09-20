<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRole>
 */
class UserRoleFactory extends Factory
{
    protected $model = UserRole::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_id' => Role::factory(),
            'company_id' => Company::factory(),
            'status' => 'activo',
            'assigned_at' => now(),
        ];
    }

    public function global(): static
    {
        return $this->state([
            'company_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => 'inactivo',
        ]);
    }
}