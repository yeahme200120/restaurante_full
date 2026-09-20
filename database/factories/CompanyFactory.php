<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'legal_name' => fake()->company(),
            'tax_id' => fake()->unique()->numerify('RFC###########'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'logo_path' => null,
            'status' => 'activo',
            'timezone' => 'America/Mexico_City',
            'locale' => 'es_MX',
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state([
            'status' => 'inactivo',
        ]);
    }
}