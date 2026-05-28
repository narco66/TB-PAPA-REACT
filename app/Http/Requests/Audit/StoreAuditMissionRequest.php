<?php

namespace App\Http\Requests\Audit;

use App\Models\Audit\AuditMission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('audit_interne.mission_create') ?? false;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:audit_plans,id'],
            'code' => ['required', 'string', 'max:32', Rule::unique('audit_missions', 'code')],
            'titre' => ['required', 'string', 'max:191'],
            'objectifs' => ['nullable', 'string', 'max:5000'],
            'perimetre' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in(AuditMission::TYPES)],
            'priorite' => ['required', Rule::in(AuditMission::PRIORITES)],
            'statut' => ['nullable', Rule::in(AuditMission::STATUTS)],
            'date_debut_prevue' => ['nullable', 'date'],
            'date_fin_prevue' => ['nullable', 'date', 'after_or_equal:date_debut_prevue'],
            'date_debut_reelle' => ['nullable', 'date'],
            'date_fin_reelle' => ['nullable', 'date', 'after_or_equal:date_debut_reelle'],
            'chef_mission_id' => ['nullable', 'integer', 'exists:users,id'],
            'departement_audite_id' => ['nullable', 'integer', 'exists:departements,id'],
            'direction_auditee_id' => ['nullable', 'integer', 'exists:directions,id'],
            'lettre_mission' => ['nullable', 'string', 'max:10000'],
            'synthese' => ['nullable', 'string', 'max:10000'],
            'equipe' => ['nullable', 'array'],
            'equipe.*.user_id' => ['required_with:equipe', 'integer', 'exists:users,id'],
            'equipe.*.role_mission' => ['required_with:equipe', Rule::in(['chef', 'auditeur_senior', 'auditeur', 'observateur', 'expert_externe'])],
        ];
    }
}
