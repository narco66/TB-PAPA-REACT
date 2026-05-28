<?php

namespace App\Http\Requests\Papa;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Requête commune aux transitions de workflow PAPA :
 * submit / approve / reject / revise / resubmit / close / archive.
 *
 * L'autorisation fine est gérée par WorkflowService (vérification permission
 * spécifique à la transition), donc cette FormRequest se contente d'exiger
 * un utilisateur authentifié.
 */
class WorkflowTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 2000 caractères.',
        ];
    }
}
