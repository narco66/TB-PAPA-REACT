<?php

namespace Database\Factories;

use App\Models\Activite;
use App\Models\SousProduit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activite>
 */
class ActiviteFactory extends Factory
{
    protected $model = Activite::class;

    public function definition(): array
    {
        return [
            'sous_produit_id' => SousProduit::factory(),
            'libelle' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'statut' => 'planifiee',
            'poids' => 100,
            'date_debut' => now(),
            'date_fin' => now()->addMonths(3),
            'niveau_risque' => 'moyen',
            'est_jalon' => false,
        ];
    }
}
