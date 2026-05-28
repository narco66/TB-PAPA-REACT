<?php

namespace App\Http\Requests\Rbm;

use App\Models\Axe;
use App\Models\SousProduit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSousProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', SousProduit::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'statut' => ['nullable', Rule::in(Axe::STATUTS)],
            'poids' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
