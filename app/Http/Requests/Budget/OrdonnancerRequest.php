<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class OrdonnancerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('ordonnancer_budget') ?? false;
    }

    public function rules(): array
    {
        return [
            'numero_piece' => ['nullable', 'string', 'max:64'],
            'motif' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
