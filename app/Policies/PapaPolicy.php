<?php

namespace App\Policies;

use App\Models\Papa;
use App\Models\User;

class PapaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('papa.viewAny');
    }

    public function view(User $user, Papa $papa): bool
    {
        return $user->can('papa.view');
    }

    public function create(User $user): bool
    {
        return $user->can('papa.create');
    }

    public function update(User $user, Papa $papa): bool
    {
        if ($papa->estVerrouille()) {
            return false;
        }

        return $user->can('papa.update');
    }

    public function delete(User $user, Papa $papa): bool
    {
        return $user->can('papa.delete') && $papa->statut === Papa::STATUT_BROUILLON;
    }

    public function submit(User $user, Papa $papa): bool
    {
        return $user->can('papa.submit') && $papa->statut === Papa::STATUT_BROUILLON;
    }

    public function validate(User $user, Papa $papa): bool
    {
        return $user->can('papa.validate') && in_array($papa->statut, [Papa::STATUT_EN_VALIDATION, Papa::STATUT_REVISE], true);
    }

    public function close(User $user, Papa $papa): bool
    {
        return $user->can('papa.close') && $papa->statut === Papa::STATUT_VALIDE;
    }

    public function archive(User $user, Papa $papa): bool
    {
        return $user->can('papa.archive') && $papa->statut === Papa::STATUT_CLOTURE;
    }
}
