<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import NavUser from '@/components/NavUser.vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { groupedNavigation } from '@/config/navigation';

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions ?? []);
const groups = computed(() => groupedNavigation(permissions.value));

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

function isItemActive(item: { href: string; exact?: boolean }): boolean {
    return item.exact
        ? isCurrentUrl(item.href)
        : isCurrentOrParentUrl(item.href);
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <FinisherLegacyLogo size="sm" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup
                v-for="group in groups"
                :key="group.group"
                class="px-2 py-0"
            >
                <SidebarGroupLabel>{{ group.label }}</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="item in group.items"
                        :key="item.href"
                    >
                        <SidebarMenuButton
                            as-child
                            :is-active="isItemActive(item)"
                            :tooltip="item.label"
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.label }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
