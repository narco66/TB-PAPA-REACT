<?php

namespace Database\Factories;

use App\Models\Activite;
use App\Models\Tache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tache>
 */
class TacheFactory extends Factory
{
    protected $model = Tache::class;

    public function definition(): array
    {
        return [
            'activite_id' => Activite::factory(),
            'libelle' => fake()->sentence(4),
            'statut' => 'planifiee',
            'poids' => 100,
            'taux_execution' => 0,
        ];
    }
}
