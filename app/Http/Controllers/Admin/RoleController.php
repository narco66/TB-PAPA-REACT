<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('role.manage');

        $roles = Role::query()
            ->withCount('permissions', 'users')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'libelle' => RolesPermissionsSeeder::ROLES[$r->name] ?? $r->name,
                'permissions_count' => $r->permissions_count,
                'users_count' => $r->users_count,
            ]),
            'role_libelles' => RolesPermissionsSeeder::ROLES,
            'can' => ['manage' => $request->user()->can('role.manage')],
        ]);
    }

    public function edit(Role $role, Request $request): Response
    {
        $this->authorize('role.manage');

        // Permissions du rôle
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        // Toutes les permissions disponibles, regroupées par domaine fonctionnel
        $all = Permission::orderBy('name')->get();
        $grouped = $all->groupBy(fn ($p) => $this->domaine($p->name));

        return Inertia::render('admin/roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'libelle' => RolesPermissionsSeeder::ROLES[$role->name] ?? $role->name,
                'users_count' => $role->users()->count(),
            ],
            'permissions_par_domaine' => $grouped->map(fn ($perms, $domaine) => [
                'domaine' => $domaine,
                'libelle_domaine' => $this->libelleDomaine($domaine),
                'permissions' => $perms->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'libelle' => $this->libellePerm($p->name),
                    'assigned' => in_array($p->name, $rolePermissions, true),
                ])->values(),
            ])->values(),
            'role_libelles' => RolesPermissionsSeeder::ROLES,
        ]);
    }

    public function update(Role $role, Request $request): RedirectResponse
    {
        $this->authorize('role.manage');

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Permissions du rôle « {$role->name} » mises à jour.");
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('role.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', Rule::unique('roles', 'name'), 'regex:/^[a-z_]+$/'],
            'libelle' => ['nullable', 'string', 'max:191'],
        ]);

        Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        return back()->with('success', "Rôle « {$data['name']} » créé.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('role.manage');

        $rolesSysteme = array_keys(RolesPermissionsSeeder::ROLES);
        if (in_array($role->name, $rolesSysteme, true)) {
            return back()->with('error', "Impossible de supprimer un rôle système (« {$role->name} »). Modifiez plutôt ses permissions.");
        }

        if ($role->users()->exists()) {
            return back()->with('error', "Impossible de supprimer : utilisateurs encore rattachés à ce rôle.");
        }

        $role->delete();

        return back()->with('success', 'Rôle supprimé.');
    }

    /** Domaine fonctionnel d'une permission (déduit du préfixe du nom). */
    protected function domaine(string $name): string
    {
        if (str_contains($name, '.')) {
            return explode('.', $name, 2)[0];
        }
        // Permissions RBM : view_axes, create_produits, etc.
        $parts = explode('_', $name);
        if (count($parts) >= 2) {
            $last = end($parts);
            if (in_array($last, ['axes', 'produits', 'sous_produits', 'activites', 'taches'], true)) {
                return 'rbm';
            }
            if (str_contains($name, '_budget') || str_contains($name, 'engager') || str_contains($name, 'liquider')) {
                return 'budget';
            }
            if (str_contains($name, '_reports') || str_contains($name, '_templates')) {
                return 'reporting';
            }
        }

        return 'autre';
    }

    protected function libelleDomaine(string $domaine): string
    {
        return match ($domaine) {
            'papa' => 'PAPA — Plan d\'Action',
            'rbm' => 'Chaîne RBM/GAR',
            'budget' => 'Budget institutionnel (IPSAS)',
            'expense' => 'Chaîne de la dépense',
            'supplier' => 'Fournisseurs',
            'document' => 'GED documentaire',
            'alerte' => 'Alertes',
            'rapport', 'reporting' => 'Reporting PDF',
            'dashboard' => 'Tableaux de bord',
            'indicateur' => 'Indicateurs CMR',
            'user' => 'Utilisateurs',
            'role' => 'Rôles et permissions',
            'permission' => 'Permissions',
            'departement', 'direction', 'service' => 'Hiérarchie organisationnelle',
            'audit' => 'Audit système',
            'audit_interne' => 'Audit interne IGS',
            'partenaire' => 'Partenaires PTF',
            'system' => 'Système',
            'two-factor' => 'Authentification 2FA',
            default => ucfirst($domaine),
        };
    }

    /** Libellé humain d'une permission. */
    protected function libellePerm(string $name): string
    {
        $map = [
            'viewAny' => 'Lister', 'view' => 'Voir', 'create' => 'Créer', 'update' => 'Modifier',
            'edit' => 'Modifier', 'delete' => 'Supprimer', 'destroy' => 'Supprimer',
            'submit' => 'Soumettre', 'validate' => 'Valider', 'reject' => 'Rejeter',
            'engage' => 'Engager', 'liquider' => 'Liquider', 'ordonnancer' => 'Ordonnancer',
            'payer' => 'Payer', 'archive' => 'Archiver', 'close' => 'Clôturer',
            'manage' => 'Gérer (tout)', 'import' => 'Importer', 'export' => 'Exporter',
            'control' => 'Contrôler', 'audit' => 'Auditer', 'saisie' => 'Saisir',
            'engager' => 'Engager', 'consomme' => 'Consommer', 'revise' => 'Réviser',
            'assign' => 'Assigner', 'resolve' => 'Résoudre', 'viewLog' => 'Consulter le journal',
            'viewConfidential' => 'Voir confidentiels',
        ];

        if (str_contains($name, '.')) {
            $action = explode('.', $name)[1] ?? '';

            return $map[$action] ?? ucfirst(str_replace('_', ' ', $action));
        }

        $verb = explode('_', $name)[0] ?? '';
        $rest = substr($name, strlen($verb) + 1);

        return ($map[$verb] ?? ucfirst($verb)) . ' — ' . str_replace('_', ' ', $rest);
    }
}
