<?php

namespace App\Policies\Budget;

use App\Models\Budget\BudgetExercice;
use App\Models\User;

class BudgetExercicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_budget');
    }

    public function view(User $user, BudgetExercice $exercice): bool
    {
        return $user->can('view_budget');
    }

    public function create(User $user): bool
    {
        return $user->can('create_budget');
    }

    public function update(User $user, BudgetExercice $exercice): bool
    {
        if ($exercice->estVerrouille()) {
            return $user->can('validate_budget') || $user->can('archive_budget');
        }

        return $user->can('edit_budget');
    }

    public function delete(User $user, BudgetExercice $exercice): bool
    {
        return $user->can('delete_budget') && $exercice->statut === 'brouillon';
    }

    public function validate(User $user, BudgetExercice $exercice): bool
    {
        return $user->can('validate_budget');
    }

    public function archive(User $user, BudgetExercice $exercice): bool
    {
        return $user->can('archive_budget');
    }

    public function import(User $user): bool
    {
        return $user->can('import_budget');
    }

    public function export(User $user): bool
    {
        return $user->can('export_budget');
    }

    public function control(User $user): bool
    {
        return $user->can('control_budget');
    }
}
