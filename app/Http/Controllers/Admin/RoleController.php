<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\PermissionCatalog;
use App\Support\SystemRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(): Response
    {
        $totalPermissions = count(PermissionCatalog::allKeys());

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderByRaw("CASE WHEN name = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label ?? $role->name,
                'description' => $role->description,
                'is_system' => SystemRoles::isSystem($role->name),
                'users_count' => $role->users_count,
                'permissions_count' => $role->name === 'super_admin' ? $totalPermissions : $role->permissions_count,
            ]);

        return Inertia::render('admin/roles/Index', ['roles' => $roles]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/roles/Create', ['modules' => PermissionCatalog::modules()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $name = Str::slug($data['label'], '_');

        if (Role::where('name', $name)->exists()) {
            $name .= '_'.Str::lower(Str::random(4));
        }

        $role = Role::query()->create([
            'name' => $name,
            'guard_name' => 'web',
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
        ]);
        $role->syncPermissions($data['permissions']);

        activity()->causedBy($request->user())->performedOn($role)->event('created')
            ->log("Rol \"{$role->label}\" creado");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol creado.']);

        return to_route('admin.roles.edit', $role);
    }

    public function edit(Role $role): Response
    {
        $role->loadCount('users');

        return Inertia::render('admin/roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => $role->label ?? $role->name,
                'description' => $role->description,
                'is_system' => SystemRoles::isSystem($role->name),
                'is_super_admin' => $role->name === 'super_admin',
                'users_count' => $role->users_count,
            ],
            'modules' => PermissionCatalog::modules(),
            'assignedPermissions' => $role->name === 'super_admin'
                ? PermissionCatalog::allKeys()
                : $role->permissions->pluck('name'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            throw ValidationException::withMessages(['permissions' => 'Super Admin tiene acceso total al sistema — no se puede editar.']);
        }

        $data = $this->validated($request);

        $role->update([
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
        ]);
        $role->syncPermissions($data['permissions']);

        activity()->causedBy($request->user())->performedOn($role)->event('updated')
            ->log("Rol \"{$role->label}\" actualizado");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol actualizado.']);

        return back();
    }

    public function duplicate(Request $request, Role $role): RedirectResponse
    {
        $label = "{$role->label} (copia)";
        $name = Str::slug($label, '_').'_'.Str::lower(Str::random(4));

        $clone = Role::query()->create([
            'name' => $name,
            'guard_name' => 'web',
            'label' => $label,
            'description' => $role->description,
        ]);
        $clone->syncPermissions($role->name === 'super_admin' ? PermissionCatalog::allKeys() : $role->permissions->pluck('name'));

        activity()->causedBy($request->user())->performedOn($clone)->event('created')
            ->log("Rol \"{$clone->label}\" duplicado de \"{$role->label}\"");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rol duplicado.']);

        return to_route('admin.roles.edit', $clone);
    }

    /**
     * @return array{label: string, description: ?string, permissions: list<string>}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists((new Permission)->getTable(), 'name')],
        ]);
    }
}
