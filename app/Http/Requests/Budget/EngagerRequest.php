<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class EngagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('engager_budget') ?? false;
    }

    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'min:0.01'],
            'beneficiaire_nom' => ['nullable', 'string', 'max:191'],
            'beneficiaire_reference' => ['nullable', 'string', 'max:64'],
            'partenaire_id' => ['nullable', 'integer', 'exists:partenaires,id'],
            'numero_piece' => ['nullable', 'string', 'max:64'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'piece_justificative' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
