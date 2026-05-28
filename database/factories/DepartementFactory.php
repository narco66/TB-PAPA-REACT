<?php

namespace Database\Factories;

use App\Models\Departement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Departement>
 */
class DepartementFactory extends Factory
{
    protected $model = Departement::class;

    public function definition(): array
    {
        return [
            'code' => 'DEP-' . fake()->unique()->numberBetween(1000, 9999),
            'libelle' => 'Département ' . fake()->word(),
            'ordre' => fake()->numberBetween(1, 10),
            'actif' => true,
        ];
    }
}
