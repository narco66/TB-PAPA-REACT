<?php

namespace Database\Factories;

use App\Models\Axe;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produit>
 */
class ProduitFactory extends Factory
{
    protected $model = Produit::class;

    public function definition(): array
    {
        return [
            'axe_id' => Axe::factory(),
            'libelle' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'statut' => 'brouillon',
            'poids' => 100,
        ];
    }
}
