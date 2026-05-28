<?php

namespace App\Http\Requests\Audit;

use App\Models\Audit\AuditConstat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditConstatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('audit_interne.constat_create') ?? false;
    }

    public function rules(): array
    {
        return [
            'mission_id' => ['required', 'integer', 'exists:audit_missions,id'],
            'code' => ['required', 'string', 'max:32'],
            'libelle' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string', 'max:10000'],
            'preuves' => ['nullable', 'string', 'max:5000'],
            'gravite' => ['required', Rule::in(AuditConstat::GRAVITES)],
            'nature' => ['required', Rule::in(AuditConstat::NATURES)],
            'cause_racine' => ['nullable', 'string', 'max:5000'],
            'impact' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $missionId = $this->input('mission_id');
            $code = $this->input('code');
            if ($missionId && $code) {
                $exists = AuditConstat::where('mission_id', $missionId)
                    ->where('code', $code)
                    ->exists();
                if ($exists) {
                    $validator->errors()->add('code', 'Ce code de constat existe déjà pour cette mission.');
                }
            }
        });
    }
}
