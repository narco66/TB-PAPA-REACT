<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de base pour les 5 niveaux de la chaîne RBM CEEAC.
 * Permissions : view_<entite>, create_<entite>, edit_<entite>, delete_<entite>, validate_<entite>.
 */
abstract class RbmPolicy
{
    abstract protected function ressource(): string;

    public function viewAny(User $user): bool
    {
        return $user->can('view_' . $this->ressource());
    }

    public function view(User $user, Model $entite): bool
    {
        return $user->can('view_' . $this->ressource());
    }

    public function create(User $user): bool
    {
        return $user->can('create_' . $this->ressource());
    }

    public function update(User $user, Model $entite): bool
    {
        if (in_array($entite->statut ?? null, ['valide', 'archive'], true)) {
            return $user->can('validate_' . $this->ressource());
        }

        return $user->can('edit_' . $this->ressource());
    }

    public function delete(User $user, Model $entite): bool
    {
        if (($entite->statut ?? null) === 'archive') {
            return false;
        }

        return $user->can('delete_' . $this->ressource());
    }

    public function validate(User $user, Model $entite): bool
    {
        return $user->can('validate_' . $this->ressource());
    }
}
