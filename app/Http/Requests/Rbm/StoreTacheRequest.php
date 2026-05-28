<?php

namespace App\Http\Requests\Rbm;

use App\Models\Tache;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tache::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'activite_id' => ['required', 'integer', 'exists:activites,id'],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'statut' => ['nullable', Rule::in(['planifiee', 'en_cours', 'realisee', 'suspendue'])],
            'poids' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'taux_execution' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigne_a_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
