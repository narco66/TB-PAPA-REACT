<?php

namespace App\Http\Requests\Budget;

use App\Models\Budget\BudgetMouvement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payer_budget') ?? false;
    }

    public function rules(): array
    {
        return [
            'mode_paiement' => ['required', Rule::in(BudgetMouvement::MODES_PAIEMENT)],
            'numero_piece' => ['nullable', 'string', 'max:64'],
            'compte_bancaire' => ['nullable', 'string', 'max:64'],
            'date_valeur' => ['nullable', 'date'],
            'motif' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
