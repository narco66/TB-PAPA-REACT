<?php

namespace App\Http\Requests\Audit;

use App\Models\Audit\AuditRecommandation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditRecommandationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('audit_interne.recommandation_create') ?? false;
    }

    public function rules(): array
    {
        return [
            'constat_id' => ['required', 'integer', 'exists:audit_constats,id'],
            'code' => ['required', 'string', 'max:32'],
            'libelle' => ['required', 'string', 'max:191'],
            'action_proposee' => ['required', 'string', 'max:10000'],
            'priorite' => ['required', Rule::in(AuditRecommandation::PRIORITES)],
            'responsable_mise_en_oeuvre_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_echeance' => ['nullable', 'date'],
            'statut' => ['nullable', Rule::in(AuditRecommandation::STATUTS)],
            'pourcentage_avancement' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $constatId = $this->input('constat_id');
            $code = $this->input('code');
            if ($constatId && $code) {
                $exists = AuditRecommandation::where('constat_id', $constatId)
                    ->where('code', $code)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('code', 'Ce code de recommandation existe déjà pour ce constat.');
                }
            }
        });
    }
}
