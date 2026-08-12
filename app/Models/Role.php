<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Extends spatie/laravel-permission's Role model purely to declare the two
 * display-only columns added on top of it (see the
 * add_label_and_description_to_roles_table migration) — no permission
 * logic changes, `name` stays the real identifier used everywhere roles are
 * checked.
 *
 * @property string|null $label
 * @property string|null $description
 */
class Role extends SpatieRole
{
    //
}
