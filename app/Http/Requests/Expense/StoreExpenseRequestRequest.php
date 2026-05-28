<?php

namespace App\Http\Requests\Expense;

use App\Models\ExpenseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('expense.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'exercice_id' => ['required', 'integer', 'exists:budget_exercices,id'],
            'departement_id' => ['nullable', 'integer', 'exists:departements,id'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'activite_id' => ['nullable', 'integer', 'exists:activites,id'],
            'tache_id' => ['nullable', 'integer', 'exists:taches,id'],
            'type_engagement' => ['required', Rule::in(array_keys(ExpenseRequest::TYPES_ENGAGEMENT))],
            'objet' => ['required', 'string', 'max:255'],
            'justification' => ['required', 'string', 'max:5000'],
            'description_detaillee' => ['nullable', 'string', 'max:10000'],
            'montant_estime' => ['required', 'numeric', 'min:0'],
            'montant_estime_ceeac' => ['nullable', 'numeric', 'min:0'],
            'montant_estime_ptf' => ['nullable', 'numeric', 'min:0'],
            'devise' => ['nullable', 'string', 'max:8'],
            'source_financement_id' => ['nullable', 'integer', 'exists:budget_sources_financement,id'],
            'supplier_pressenti_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'date_besoin_prevu' => ['nullable', 'date'],
            'date_livraison_souhaitee' => ['nullable', 'date'],
            'priorite' => ['nullable', 'integer', 'between:1,4'],
        ];
    }
}
