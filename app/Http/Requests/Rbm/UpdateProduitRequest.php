<?php

namespace App\Http\Requests\Rbm;

use App\Models\Axe;
use App\Models\Produit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProduitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $produit = $this->route('produit');

        return $produit instanceof Produit
            && ($this->user()?->can('update', $produit) ?? false);
    }

    public function rules(): array
    {
        return [
            'axe_id' => ['required', 'integer', 'exists:axes,id'],
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
