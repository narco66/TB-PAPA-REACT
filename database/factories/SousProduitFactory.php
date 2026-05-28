<?php

namespace Database\Factories;

use App\Models\Produit;
use App\Models\SousProduit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SousProduit>
 */
class SousProduitFactory extends Factory
{
    protected $model = SousProduit::class;

    public function definition(): array
    {
        return [
            'produit_id' => Produit::factory(),
            'libelle' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'statut' => 'brouillon',
            'poids' => 100,
        ];
    }
}
