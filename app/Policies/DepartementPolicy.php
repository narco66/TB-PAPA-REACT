<?php

namespace App\Policies;

use App\Models\Departement;
use App\Models\User;

class DepartementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('departement.manage') || $user->estPresident() || $user->estVicePresident()
            || $user->estSecretaireGeneral() || $user->estCommissaire();
    }

    public function view(User $user, Departement $departement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('departement.manage');
    }

    public function update(User $user, Departement $departement): bool
    {
        return $user->can('departement.manage');
    }

    public function delete(User $user, Departement $departement): bool
    {
        if ($departement->directions()->exists() || $departement->axes()->exists()) {
            return false;
        }

        return $user->can('departement.manage');
    }
}
