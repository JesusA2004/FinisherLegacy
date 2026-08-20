import {
    AlertTriangle,
    Award,
    Boxes,
    Building2,
    Calendar,
    CalendarDays,
    ClipboardList,
    Compass,
    Cpu,
    Factory,
    Flag,
    History,
    KeyRound,
    LayoutGrid,
    Palette,
    QrCode,
    Settings2,
    ShieldCheck,
    Trophy,
    UserCheck,
    UserCircle,
    UserCog,
    Users,
    Upload,
    Zap,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';

/**
 * Maps the icon name strings stored in config/permissions.php / navigation.ts
 * (plain strings, since PHP can't reference Vue components) to the actual Lucide
 * component — one lookup shared by the roles permission matrix and the app-wide
 * sidebar/command menu, so both read the same icon for the same module.
 */
const iconMap: Record<string, LucideIcon> = {
    AlertTriangle,
    Award,
    Boxes,
    Building2,
    Calendar,
    CalendarDays,
    ClipboardList,
    Compass,
    Cpu,
    Factory,
    Flag,
    History,
    KeyRound,
    LayoutGrid,
    Palette,
    QrCode,
    Settings2,
    ShieldCheck,
    Trophy,
    UserCheck,
    UserCircle,
    UserCog,
    Users,
    Upload,
    Zap,
};

export function resolveIcon(name: string): LucideIcon {
    return iconMap[name] ?? Boxes;
}
