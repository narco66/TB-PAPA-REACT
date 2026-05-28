<?php

namespace App\Http\Requests\Indicateur;

use App\Models\Indicateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIndicateurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Indicateur::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'sous_produit_id' => ['required', 'integer', 'exists:sous_produits,id'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('indicateurs', 'code')->where(fn ($q) => $q->where('sous_produit_id', $this->input('sous_produit_id'))),
            ],
            'libelle' => ['required', 'string', 'max:255'],
            'definition' => ['nullable', 'string', 'max:5000'],

            // === Typologie CAD/OCDE ===
            'type' => ['required', Rule::in(Indicateur::TYPES)],
            'categorie' => ['required', Rule::in(Indicateur::CATEGORIES)],
            'polarite' => ['nullable', Rule::in(Indicateur::POLARITES)],
            'unite' => ['nullable', 'string', 'max:32'],

            // === Baseline / cible / paliers trimestriels ===
            'baseline' => ['nullable', 'numeric'],
            'cible' => ['nullable', 'numeric'],
            'date_baseline' => ['nullable', 'date'],
            'palier_t1' => ['nullable', 'numeric'],
            'palier_t2' => ['nullable', 'numeric'],
            'palier_t3' => ['nullable', 'numeric'],
            'palier_t4' => ['nullable', 'numeric'],

            // === Seuils d'alerte ===
            'seuil_alerte_bas' => ['nullable', 'numeric'],
            'seuil_alerte_haut' => ['nullable', 'numeric'],

            // === Méthodologie complète ===
            'methode_calcul' => ['nullable', 'string', 'max:5000'],
            'hypotheses' => ['nullable', 'string', 'max:5000'],
            'risques_associes' => ['nullable', 'string', 'max:5000'],
            'frequence_collecte' => ['required', Rule::in(Indicateur::FREQUENCES)],
            'source_donnees' => ['nullable', 'string', 'max:255'],
            'instrument_collecte' => ['nullable', 'string', 'max:255'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'responsable_collecte_id' => ['nullable', 'integer', 'exists:users,id'],

            // === Désagrégation ===
            'desagregation_genre' => ['nullable', 'boolean'],
            'desagregation_geographique' => ['nullable', 'boolean'],
            'desagregation_vulnerabilite' => ['nullable', 'boolean'],
            'desagregation_age' => ['nullable', 'boolean'],

            // === Référentiels externes (ODD, Agenda 2063, etc.) ===
            'referentiels_externes' => ['nullable', 'array'],
            'referentiels_externes.*.cadre' => ['required_with:referentiels_externes', 'string', 'max:64'],
            'referentiels_externes.*.code' => ['required_with:referentiels_externes', 'string', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code est déjà utilisé pour ce Sous-Produit.',
            'categorie.in' => 'Catégorie OCDE invalide (impact / effet / produit / processus).',
        ];
    }
}
