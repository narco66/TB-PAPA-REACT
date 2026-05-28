<?php

namespace App\Policies;

use App\Models\Indicateur;
use App\Models\User;
use App\Models\ValeurIndicateur;

class IndicateurPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('indicateur.viewAny');
    }

    public function view(User $user, Indicateur $indicateur): bool
    {
        return $user->can('indicateur.view');
    }

    public function create(User $user): bool
    {
        return $user->can('indicateur.create');
    }

    public function update(User $user, Indicateur $indicateur): bool
    {
        return $user->can('indicateur.update');
    }

    public function delete(User $user, Indicateur $indicateur): bool
    {
        return $user->can('indicateur.delete');
    }

    public function saisie(User $user, Indicateur $indicateur): bool
    {
        if (! $user->can('indicateur.saisie')) {
            return false;
        }
        if ($user->estPointFocal() || $user->estChefService() ?? false) {
            return $indicateur->responsable_id === $user->id
                || $indicateur->responsable_id === null;
        }

        return true;
    }

    public function validateValeur(User $user, ValeurIndicateur $valeur): bool
    {
        return $user->can('indicateur.validate');
    }
}
