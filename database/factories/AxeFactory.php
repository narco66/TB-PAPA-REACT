<?php

namespace Database\Factories;

use App\Models\Axe;
use App\Models\Papa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Axe>
 */
class AxeFactory extends Factory
{
    protected $model = Axe::class;

    public function definition(): array
    {
        return [
            'papa_id' => Papa::factory(),
            'libelle' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'statut' => 'brouillon',
            'poids' => 100,
            'date_debut' => now(),
            'date_fin' => now()->addYear(),
        ];
    }

    public function valide(): static
    {
        return $this->state(fn () => ['statut' => 'valide']);
    }
}
