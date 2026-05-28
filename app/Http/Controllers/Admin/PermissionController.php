<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('permission.manage');

        $roles = Role::orderBy('name')->get(['id', 'name']);
        $rolesNames = $roles->pluck('name');

        // Permissions × rôles : matrice de couverture
        $permissions = Permission::with(['roles' => fn ($q) => $q->select('roles.id', 'roles.name')])
            ->orderBy('name')
            ->get();

        $matrice = $permissions->map(function ($p) use ($rolesNames) {
            $assigned = $p->roles->pluck('name')->toArray();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'roles' => $rolesNames->mapWithKeys(fn ($r) => [$r => in_array($r, $assigned, true)])->all(),
                'roles_count' => count($assigned),
            ];
        });

        return Inertia::render('admin/permissions/index', [
            'permissions' => $matrice,
            'roles' => $roles,
            'role_libelles' => RolesPermissionsSeeder::ROLES,
        ]);
    }
}
