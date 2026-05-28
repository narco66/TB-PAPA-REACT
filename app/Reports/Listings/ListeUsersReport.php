<?php

namespace App\Reports\Listings;

use App\Models\User;
use Illuminate\Support\Collection;

class ListeUsersReport extends ListingReport
{
    public function key(): string
    {
        return 'liste_users';
    }

    public function titre(): string
    {
        return 'Liste des utilisateurs';
    }

    public function description(): string
    {
        return 'Comptes utilisateurs institutionnels avec rôles et statut.';
    }

    public function permission(): string
    {
        return 'user.viewAny';
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Matricule', 'key' => 'matricule', 'width' => '10%'],
            ['label' => 'Nom', 'key' => 'name'],
            ['label' => 'Email', 'key' => 'email', 'width' => '22%'],
            ['label' => 'Fonction', 'key' => 'fonction', 'width' => '18%'],
            ['label' => 'Rôles', 'key' => 'roles', 'width' => '20%'],
            ['label' => 'Actif', 'key' => 'actif', 'kind' => 'badge', 'width' => '8%'],
        ];
    }

    protected function rows(array $filtres): Collection
    {
        $q = User::query()->with('roles:id,name');

        if ($search = $this->filtre($filtres, 'q')) {
            $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('matricule', 'like', "%{$search}%"));
        }
        if ($actif = $this->filtre($filtres, 'actif')) {
            $q->where('actif', $actif === '1' || $actif === 'true');
        }

        return $q->orderBy('name')->limit(1000)->get()->map(fn ($u) => [
            'matricule' => $u->matricule ?? '—',
            'name' => $u->name,
            'email' => $u->email,
            'fonction' => $u->fonction ?? '—',
            'roles' => $u->roles->pluck('name')->implode(', ') ?: '—',
            'actif' => $u->actif ? 'Actif' : 'Inactif',
        ]);
    }
}
