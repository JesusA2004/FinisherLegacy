<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1.5"
        aria-label="Paginación"
    >
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="flex size-9 items-center justify-center rounded-md text-sm text-white/20"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                class="flex size-9 items-center justify-center rounded-md border text-sm transition-colors"
                :class="
                    link.active
                        ? 'border-fl-gold/40 bg-fl-gold text-fl-black'
                        : 'border-white/10 text-white/70 hover:border-fl-gold/30 hover:text-fl-gold'
                "
            >
                <span v-html="link.label" />
            </Link>
        </template>
    </nav>
</template>
