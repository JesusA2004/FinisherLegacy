<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Award, Compass, LayoutGrid, UserCircle } from '@lucide/vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as medalsIndex } from '@/routes/dashboard/medals';
import { edit as editProfile } from '@/routes/dashboard/profile';
import { index as eventsIndex } from '@/routes/events';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Mi Legacy Profile',
        href: editProfile(),
        icon: UserCircle,
    },
    {
        title: 'Mis Medallas',
        href: medalsIndex(),
        icon: Award,
    },
    {
        title: 'Explorar Eventos',
        href: eventsIndex(),
        icon: Compass,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <FinisherLegacyLogo size="sm" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
