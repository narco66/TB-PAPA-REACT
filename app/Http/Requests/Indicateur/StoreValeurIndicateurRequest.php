<?php

namespace App\Http\Requests\Indicateur;

use App\Models\Indicateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreValeurIndicateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        $indicateur = $this->route('indicateur');

        return $indicateur instanceof Indicateur
            && ($this->user()?->can('saisie', $indicateur) ?? false);
    }

    public function rules(): array
    {
        return [
            'date_observation' => ['required', 'date'],
            'periode_libelle' => ['nullable', 'string', 'max:32'],
            'trimestre' => ['nullable', Rule::in(Indicateur::TRIMESTRES)],
            'annee' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'valeur' => ['required', 'numeric'],

            // Désagrégation genre
            'valeur_hommes' => ['nullable', 'numeric'],
            'valeur_femmes' => ['nullable', 'numeric'],

            // Désagrégation libre (JSON)
            'desagregation_age' => ['nullable', 'array'],
            'desagregation_geographique' => ['nullable', 'array'],
            'desagregation_vulnerabilite' => ['nullable', 'array'],

            'commentaire' => ['nullable', 'string', 'max:2000'],
            'source_verification' => ['nullable', 'string', 'max:255'],
        ];
    }
}
