/**
 * Single source of truth for the ENTIRE app's navigation — one sidebar for
 * every authenticated user, athlete and staff alike. The desktop sidebar, the
 * mobile bottom nav + "Más" sheet, and the Ctrl/Cmd+K command menu all read
 * this same list instead of keeping separate item arrays in sync.
 *
 * `permission` is a string from the shared `auth.permissions` prop
 * (App\Support\PermissionCatalog — see HandleInertiaRequests::share()). `null`
 * means "visible to any authenticated user" (the Mi Legacy items, which every
 * account — athlete or staff — can reach). Every href below points at a route
 * that exists today — nothing here is a dead link waiting for a future module.
 */
import type { LucideIcon } from '@lucide/vue';
import { resolveIcon } from '@/lib/iconMap';

export type NavGroup =
    | 'legacy'
    | 'resumen'
    | 'personas'
    | 'eventos'
    | 'placas'
    | 'operacion'
    | 'sistema';

export interface NavItem {
    label: string;
    icon: LucideIcon;
    href: string;
    permission: string | null;
    group: NavGroup;
    mobilePriority?: boolean;
    /** This href is also a literal prefix of other routes (e.g. "/admin" is a
     * prefix of "/admin/users"), so it needs an exact match to be "active" —
     * everything else correctly wants prefix matching. */
    exact?: boolean;
}

export const navGroupLabels: Record<NavGroup, string> = {
    legacy: 'Mi Legacy',
    resumen: 'Panel administrativo',
    personas: 'Personas',
    eventos: 'Eventos',
    placas: 'Placas',
    operacion: 'Operación',
    sistema: 'Sistema',
};

export const navigation: NavItem[] = [
    {
        label: 'Inicio',
        icon: resolveIcon('LayoutGrid'),
        href: '/dashboard',
        permission: null,
        group: 'legacy',
        exact: true,
        mobilePriority: true,
    },
    {
        label: 'Mi perfil',
        icon: resolveIcon('UserCircle'),
        href: '/dashboard/profile/edit',
        permission: null,
        group: 'legacy',
    },
    {
        label: 'Mis medallas',
        icon: resolveIcon('Award'),
        href: '/dashboard/medals',
        permission: null,
        group: 'legacy',
        mobilePriority: true,
    },
    {
        label: 'Explorar eventos',
        icon: resolveIcon('Compass'),
        href: '/events',
        permission: null,
        group: 'legacy',
    },

    {
        label: 'Inicio',
        icon: resolveIcon('LayoutGrid'),
        href: '/admin',
        permission: 'dashboard.admin.view',
        group: 'resumen',
        exact: true,
    },

    {
        label: 'Usuarios',
        icon: resolveIcon('Users'),
        href: '/admin/users',
        permission: 'users.view',
        group: 'personas',
    },
    {
        label: 'Atletas',
        icon: resolveIcon('UserCircle'),
        href: '/admin/athletes',
        permission: 'athletes.view',
        group: 'personas',
    },
    {
        label: 'Conflictos de identidad',
        icon: resolveIcon('ShieldQuestion'),
        href: '/admin/identity-conflicts',
        permission: 'athletes.manage',
        group: 'personas',
    },
    {
        label: 'Roles y permisos',
        icon: resolveIcon('ShieldCheck'),
        href: '/admin/roles',
        permission: 'roles.manage',
        group: 'personas',
    },

    {
        label: 'Organizadores',
        icon: resolveIcon('Building2'),
        href: '/admin/organizers',
        permission: 'organizers.view',
        group: 'eventos',
    },
    {
        label: 'Eventos y ediciones',
        icon: resolveIcon('Calendar'),
        href: '/admin/editions',
        permission: 'events.view',
        group: 'eventos',
        mobilePriority: true,
    },
    {
        label: 'Prerregistros',
        icon: resolveIcon('ClipboardList'),
        href: '/admin/preregistrations',
        permission: 'preregistrations.view',
        group: 'eventos',
    },
    {
        label: 'Participantes',
        icon: resolveIcon('UserCheck'),
        href: '/admin/participants',
        permission: 'participants.view',
        group: 'eventos',
    },
    {
        label: 'Importaciones',
        icon: resolveIcon('Upload'),
        href: '/imports',
        permission: 'imports.manage',
        group: 'eventos',
    },
    {
        label: 'Integraciones',
        icon: resolveIcon('Plug'),
        href: '/admin/integrations',
        permission: 'integrations.view',
        group: 'eventos',
    },

    {
        label: 'Placas',
        icon: resolveIcon('Boxes'),
        href: '/admin/plates',
        permission: 'plates.view',
        group: 'placas',
        mobilePriority: true,
    },
    {
        label: 'Plate Studio',
        icon: resolveIcon('Palette'),
        href: '/admin/plate-studio',
        permission: 'platetemplates.view',
        group: 'placas',
    },
    {
        label: 'Máquinas',
        icon: resolveIcon('Settings2'),
        href: '/admin/machine-profiles',
        permission: 'platetemplates.view',
        group: 'placas',
    },
    {
        label: 'Legacy Codes',
        icon: resolveIcon('QrCode'),
        href: '/admin/legacy-codes',
        permission: 'legacycodes.view',
        group: 'placas',
    },

    {
        label: 'Event OS',
        icon: resolveIcon('Zap'),
        href: '/operator',
        permission: 'operator.access',
        group: 'operacion',
        mobilePriority: true,
    },
    {
        label: 'Producción',
        icon: resolveIcon('Factory'),
        href: '/production',
        permission: 'production.access',
        group: 'operacion',
    },
    {
        label: 'Estaciones',
        icon: resolveIcon('Cpu'),
        href: '/admin/production-devices',
        permission: 'productiondevices.view',
        group: 'operacion',
    },
    {
        label: 'Incidencias',
        icon: resolveIcon('AlertTriangle'),
        href: '/admin/incidents',
        permission: 'incidents.view',
        group: 'operacion',
    },

    {
        label: 'Auditoría',
        icon: resolveIcon('History'),
        href: '/admin/audit',
        permission: 'audit.view',
        group: 'sistema',
    },
];

export function visibleNavigation(permissions: string[]): NavItem[] {
    const set = new Set(permissions);

    return navigation.filter(
        (item) => item.permission === null || set.has(item.permission),
    );
}

export function groupedNavigation(
    permissions: string[],
): { group: NavGroup; label: string; items: NavItem[] }[] {
    const visible = visibleNavigation(permissions);
    const groups: NavGroup[] = [
        'legacy',
        'resumen',
        'personas',
        'eventos',
        'placas',
        'operacion',
        'sistema',
    ];

    return groups
        .map((group) => ({
            group,
            label: navGroupLabels[group],
            items: visible.filter((item) => item.group === group),
        }))
        .filter((entry) => entry.items.length > 0);
}
