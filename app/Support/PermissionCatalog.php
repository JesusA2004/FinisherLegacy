<?php

namespace App\Support;

/**
 * Reads config/permissions.php — the single source of truth for every permission
 * key in the system. RolePermissionSeeder creates permissions from here, and the
 * admin UI (menu filtering, the roles permission matrix) reads the same structure,
 * so there is never a second list to keep in sync.
 */
class PermissionCatalog
{
    /**
     * @return array<string, array{label: string, icon: string, permissions: array<string, string>}>
     */
    public static function modules(): array
    {
        return config('permissions', []);
    }

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        $keys = [];

        foreach (self::modules() as $module) {
            array_push($keys, ...array_keys($module['permissions']));
        }

        return $keys;
    }

    public static function label(string $permission): string
    {
        foreach (self::modules() as $module) {
            if (isset($module['permissions'][$permission])) {
                return $module['permissions'][$permission];
            }
        }

        return $permission;
    }
}
