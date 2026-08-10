<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('roles')
            ->when($request->string('q')->toString(), fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('role')->toString(), fn ($q, $role) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $users->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'roles' => $user->roles->pluck('name')->implode(', ') ?: '—',
            'last_login_at' => $user->last_login_at?->diffForHumans() ?? 'Nunca',
        ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
            'currentUserId' => $request->user()->id,
            'currentUserIsSuperAdmin' => $request->user()->hasRole('super_admin'),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'role' => $request->string('role')->toString(),
            ],
        ]);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);

        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['status' => 'No puedes cambiar tu propio estado.']);
        }

        $newStatus = UserStatus::from($data['status']);

        if ($newStatus !== UserStatus::Active && $this->isLastActiveSuperAdmin($user)) {
            throw ValidationException::withMessages(['status' => 'No puedes dejar el sistema sin un super_admin activo.']);
        }

        $previousStatus = $user->status;
        $user->status = $newStatus;
        $user->save();

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->event('updated')
            ->withProperties(['from' => $previousStatus->value, 'to' => $newStatus->value])
            ->log("Estado de {$user->name} cambiado de {$previousStatus->value} a {$newStatus->value}");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Estado del usuario actualizado.']);

        return back();
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $newRoles = $data['roles'] ?? [];
        $grantingSuperAdmin = in_array('super_admin', $newRoles, true) && ! $user->hasRole('super_admin');
        $revokingSuperAdmin = ! in_array('super_admin', $newRoles, true) && $user->hasRole('super_admin');

        if ($grantingSuperAdmin && ! $request->user()->hasRole('super_admin')) {
            throw ValidationException::withMessages(['roles' => 'Solo un super_admin puede otorgar el rol super_admin.']);
        }

        if ($revokingSuperAdmin && $this->isLastActiveSuperAdmin($user)) {
            throw ValidationException::withMessages(['roles' => 'No puedes quitar el único super_admin activo del sistema.']);
        }

        $previousRoles = $user->roles->pluck('name')->all();
        $user->syncRoles($newRoles);

        activity()
            ->causedBy($request->user())
            ->performedOn($user)
            ->event('updated')
            ->withProperties(['from' => $previousRoles, 'to' => $newRoles])
            ->log("Roles de {$user->name} actualizados");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Roles actualizados.']);

        return back();
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $user->hasRole('super_admin')) {
            return false;
        }

        return User::role('super_admin')
            ->where('status', UserStatus::Active)
            ->where('id', '!=', $user->id)
            ->doesntExist();
    }
}
