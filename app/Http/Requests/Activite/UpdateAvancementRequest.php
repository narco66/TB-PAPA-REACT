<?php

namespace App\Http\Requests\Activite;

use App\Models\Activite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvancementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activite = $this->route('activite');

        return $activite instanceof Activite
            && ($this->user()?->can('update', $activite) ?? false);
    }

    public function rules(): array
    {
        return [
            'taux_execution' => ['required', 'numeric', 'min:0', 'max:100'],
            'statut' => ['required', Rule::in(['planifiee', 'en_cours', 'realisee', 'suspendue', 'annulee'])],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
