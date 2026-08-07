<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Modules covered by Phase 1 + Phase 2. Each gets a "view" and a "manage"
     * (create/update/delete) permission, which keeps the matrix simple while still
     * letting a role see something without being able to change it.
     *
     * @var list<string>
     */
    private const MODULES = [
        'users', 'athletes', 'organizers', 'events', 'editions', 'races',
        'participants', 'results', 'preregistrations', 'medals', 'plates',
        'legacycodes', 'production', 'imports', 'incidents', 'operators',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::MODULES as $module) {
            Permission::findOrCreate("{$module}.view");
            Permission::findOrCreate("{$module}.manage");
        }

        Permission::findOrCreate('audit.view');
        Permission::findOrCreate('dashboard.admin.view');
        Permission::findOrCreate('operator.access');
        Permission::findOrCreate('production.access');

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions(Permission::all());

        // super_admin does not need explicit permissions: Gate::before in
        // AppServiceProvider grants it everything unconditionally.
        Role::findOrCreate('super_admin');

        Role::findOrCreate('event_manager')->syncPermissions([
            'events.view', 'editions.view', 'editions.manage', 'races.manage',
            'participants.manage', 'results.manage', 'preregistrations.manage',
            'imports.manage', 'incidents.manage', 'plates.view', 'legacycodes.view',
            'operators.manage',
        ]);

        Role::findOrCreate('event_operator')->syncPermissions([
            'participants.view', 'results.view', 'plates.manage', 'legacycodes.manage',
            'incidents.manage', 'operator.access',
        ]);

        Role::findOrCreate('production_operator')->syncPermissions([
            'plates.view', 'production.manage', 'production.access', 'incidents.manage',
        ]);

        Role::findOrCreate('athlete');
    }
}
