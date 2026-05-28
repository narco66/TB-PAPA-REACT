<?php

namespace App\Http\Requests\Audit;

use App\Models\Audit\AuditSuiviRecommandation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditSuiviRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('audit_interne.suivi_create') ?? false;
    }

    public function rules(): array
    {
        return [
            'recommandation_id' => ['required', 'integer', 'exists:audit_recommandations,id'],
            'date_suivi' => ['required', 'date'],
            'etat_avancement' => ['required', Rule::in(AuditSuiviRecommandation::ETATS)],
            'pourcentage' => ['required', 'integer', 'min:0', 'max:100'],
            'actions_realisees' => ['nullable', 'string', 'max:5000'],
            'actions_restantes' => ['nullable', 'string', 'max:5000'],
            'blocages' => ['nullable', 'string', 'max:5000'],
            'commentaire' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
