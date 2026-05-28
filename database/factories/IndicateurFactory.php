<?php

namespace Database\Factories;

use App\Models\Indicateur;
use App\Models\SousProduit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicateur>
 */
class IndicateurFactory extends Factory
{
    protected $model = Indicateur::class;

    public function definition(): array
    {
        return [
            'sous_produit_id' => SousProduit::factory(),
            'code' => 'IND-' . fake()->unique()->numberBetween(1000, 9999),
            'libelle' => fake()->sentence(5),
            'definition' => fake()->paragraph(),
            'type' => 'quantitatif',
            'categorie' => 'produit',
            'polarite' => 'positive',
            'unite' => '%',
            'baseline' => 0,
            'cible' => 100,
            'frequence_collecte' => 'trimestrielle',
        ];
    }
}
