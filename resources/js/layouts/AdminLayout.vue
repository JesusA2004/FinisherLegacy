<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Award,
    Boxes,
    Building2,
    Calendar,
    ClipboardList,
    History,
    LayoutGrid,
    QrCode,
    Upload,
    UserCog,
    Users,
} from '@lucide/vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const { isCurrentOrParentUrl } = useCurrentUrl();

const nav = [
    { label: 'Resumen', href: '/admin', icon: LayoutGrid },
    { label: 'Eventos y ediciones', href: '/admin/editions', icon: Calendar },
    { label: 'Prerregistros', href: '/admin/preregistrations', icon: ClipboardList },
    { label: 'Participantes', href: '/admin/participants', icon: Users },
    { label: 'Placas', href: '/admin/plates', icon: Boxes },
    { label: 'Legacy Codes', href: '/admin/legacy-codes', icon: QrCode },
    { label: 'Incidencias', href: '/admin/incidents', icon: Award },
    { label: 'Importaciones', href: '/imports', icon: Upload },
    { label: 'Producción', href: '/production', icon: Boxes },
    { label: 'Event OS', href: '/operator', icon: Users },
    { label: 'Usuarios', href: '/admin/users', icon: UserCog },
    { label: 'Organizadores', href: '/admin/organizers', icon: Building2 },
    { label: 'Auditoría', href: '/admin/audit', icon: History },
];
</script>

<template>
    <div class="dark min-h-svh bg-fl-black">
        <div class="flex">
            <aside class="hidden w-60 shrink-0 border-r border-white/10 p-4 lg:block">
                <Link href="/admin" class="mb-6 block px-2">
                    <FinisherLegacyLogo size="sm" />
                </Link>
                <nav class="space-y-1">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-fl-gold/10 text-fl-gold'
                                : 'text-white/60 hover:bg-white/5 hover:text-white'
                        "
                    >
                        <component :is="item.icon" class="size-4" />
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>

            <main class="min-w-0 flex-1">
                <slot />
            </main>
        </div>
        <Toaster />
    </div>
</template>
