<?php

namespace App\Http\Requests\Rbm;

use App\Models\Axe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAxeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $axe = $this->route('axe');

        return $axe instanceof Axe
            && ($this->user()?->can('update', $axe) ?? false);
    }

    public function rules(): array
    {
        return [
            'papa_id' => ['required', 'integer', 'exists:papas,id'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'statut' => ['nullable', Rule::in(Axe::STATUTS)],
            'poids' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
