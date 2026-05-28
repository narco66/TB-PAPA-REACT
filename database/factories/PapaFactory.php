<?php

namespace Database\Factories;

use App\Models\Papa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Papa>
 */
class PapaFactory extends Factory
{
    protected $model = Papa::class;

    public function definition(): array
    {
        $annee = fake()->unique()->numberBetween(2024, 9999);

        return [
            'annee' => $annee,
            'version' => '1.0',
            'libelle' => "Plan d'Action Prioritaire Annuel {$annee}",
            'description' => fake()->paragraph(),
            'perimetre_institutionnel' => 'Présidence, Vice-Présidence, Départements techniques.',
            'statut' => Papa::STATUT_BROUILLON,
            'date_debut' => "{$annee}-01-01",
            'date_fin' => "{$annee}-12-31",
            'verrouille' => false,
        ];
    }

    public function valide(): static
    {
        return $this->state(fn () => [
            'statut' => Papa::STATUT_VALIDE,
            'date_validation' => now(),
        ]);
    }

    public function cloture(): static
    {
        return $this->state(fn () => [
            'statut' => Papa::STATUT_CLOTURE,
            'cloture_le' => now(),
            'verrouille' => true,
        ]);
    }
}
