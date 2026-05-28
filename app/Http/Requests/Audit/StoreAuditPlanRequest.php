<?php

namespace App\Http\Requests\Audit;

use App\Models\Audit\AuditPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('audit_interne.plan_create') ?? false;
    }

    public function rules(): array
    {
        return [
            'annee' => ['required', 'integer', 'min:2024', 'max:2050', Rule::unique('audit_plans', 'annee')],
            'libelle' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:5000'],
            'orientation_strategique' => ['nullable', 'string', 'max:5000'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'statut' => ['nullable', Rule::in(AuditPlan::STATUTS)],
        ];
    }
}
