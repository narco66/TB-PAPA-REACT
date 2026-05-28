<?php

namespace App\Policies\Budget;

use App\Models\Budget\BudgetLigne;
use App\Models\User;

class BudgetLignePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_budget');
    }

    public function view(User $user, BudgetLigne $ligne): bool
    {
        return $user->can('view_budget');
    }

    public function create(User $user): bool
    {
        return $user->can('create_budget');
    }

    public function update(User $user, BudgetLigne $ligne): bool
    {
        if ($ligne->exercice?->estVerrouille()) {
            return false;
        }
        if (in_array($ligne->statut, ['valide', 'archive'], true)) {
            return $user->can('validate_budget');
        }

        return $user->can('edit_budget');
    }

    public function delete(User $user, BudgetLigne $ligne): bool
    {
        return $user->can('delete_budget') && $ligne->statut !== 'archive';
    }

    public function validate(User $user, BudgetLigne $ligne): bool
    {
        return $user->can('validate_budget');
    }
}
