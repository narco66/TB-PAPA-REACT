<?php

namespace App\Http\Requests\Activite;

use App\Models\Activite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActiviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Activite::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'sous_produit_id' => ['required', 'integer', 'exists:sous_produits,id'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'poids' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'point_focal_id' => ['nullable', 'integer', 'exists:users,id'],
            'niveau_risque' => ['nullable', Rule::in(['faible', 'moyen', 'eleve', 'critique'])],
            'est_jalon' => ['nullable', 'boolean'],
        ];
    }
}
