<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\User;
use App\Rules\StrongPassword;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $request->user()->can('user.viewAny') || abort(403);

        $query = User::query()->with(['roles:id,name', 'direction:id,code,libelle']);

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('matricule', 'like', "%{$q}%"),
            );
        }
        if ($role = $request->string('role')->toString()) {
            $query->role($role);
        }
        if ($request->boolean('inactifs')) {
            $query->where('actif', false);
        } else {
            $query->where('actif', true);
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name')->toArray(),
            'role_libelles' => RolesPermissionsSeeder::ROLES,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'role' => $request->string('role')->toString(),
                'inactifs' => $request->boolean('inactifs'),
            ],
            'can' => ['create' => $request->user()->can('user.create')],
        ]);
    }

    public function create(Request $request): Response
    {
        $request->user()->can('user.create') || abort(403);

        return Inertia::render('admin/users/create', [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'role_libelles' => RolesPermissionsSeeder::ROLES,
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->can('user.create') || abort(403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'matricule' => ['nullable', 'string', 'max:32', 'unique:users,matricule'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword(12)],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'matricule' => $validated['matricule'] ?? null,
            'fonction' => $validated['fonction'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'direction_id' => $validated['direction_id'] ?? null,
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'actif' => true,
            'email_verified_at' => now(),
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('admin.users.index')->with('success', "Utilisateur {$user->name} créé.");
    }

    public function edit(User $user): Response
    {
        auth()->user()->can('user.update') || abort(403);

        return Inertia::render('admin/users/edit', [
            'user' => array_merge($user->toArray(), [
                'roles' => $user->getRoleNames()->toArray(),
            ]),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'role_libelles' => RolesPermissionsSeeder::ROLES,
            'directions' => Direction::orderBy('libelle')->get(['id', 'code', 'libelle']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->user()->can('user.update') || abort(403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'matricule' => ['nullable', 'string', 'max:32', Rule::unique('users')->ignore($user->id)],
            'fonction' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:32'],
            'direction_id' => ['nullable', 'integer', 'exists:directions,id'],
            'actif' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'password' => ['nullable', 'string', 'confirmed', new StrongPassword(12)],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'matricule' => $validated['matricule'] ?? null,
            'fonction' => $validated['fonction'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
            'direction_id' => $validated['direction_id'] ?? null,
            'actif' => (bool) ($validated['actif'] ?? true),
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->password_changed_at = now();
        }

        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        auth()->user()->can('user.delete') || abort(403);
        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas vous désactiver vous-même.');

        $user->update(['actif' => false]);

        return back()->with('success', 'Utilisateur désactivé.');
    }
}
