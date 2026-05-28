<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class LiquiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('liquider_budget') ?? false;
    }

    public function rules(): array
    {
        return [
            'montant' => ['required', 'numeric', 'min:0.01'],
            'numero_piece' => ['nullable', 'string', 'max:64'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'piece_justificative' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
