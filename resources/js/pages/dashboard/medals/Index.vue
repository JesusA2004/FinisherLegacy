<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Award, Plus } from '@lucide/vue';
import MascotEmptyState from '@/components/public/MascotEmptyState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/dashboard/medals';
import type { MedalCard } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Mis Medallas', href: index() },
        ],
    },
});

defineProps<{
    medals: MedalCard[];
}>();
</script>

<template>
    <Head title="Mis Medallas" />

    <div class="p-4 md:p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-white">Mis Medallas</h1>
                <p class="mt-1 text-sm text-white/50">
                    Tu vitrina digital de logros.
                </p>
            </div>
            <Button
                as-child
                class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
            >
                <Link :href="create()">
                    <Plus class="size-4" />
                    Agregar medalla
                </Link>
            </Button>
        </div>

        <MascotEmptyState
            v-if="medals.length === 0"
            title="Tu colección comienza con una meta."
            description="Registra tu primera medalla y empieza a construir tu Legacy."
        >
            <Button
                as-child
                class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
            >
                <Link :href="create()">Agregar mi primera medalla</Link>
            </Button>
        </MascotEmptyState>

        <div
            v-else
            class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
        >
            <Link
                v-for="medal in medals"
                :key="medal.id"
                :href="show(medal.id)"
                class="group overflow-hidden rounded-xl border border-white/10 bg-fl-graphite/40 transition-colors hover:border-fl-gold/30"
            >
                <div
                    class="relative aspect-square bg-gradient-to-br from-fl-graphite-light to-fl-black"
                >
                    <img
                        v-if="medal.thumbnail_url"
                        :src="medal.thumbnail_url"
                        :alt="medal.title"
                        loading="lazy"
                        class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div
                        v-else
                        class="flex size-full items-center justify-center"
                    >
                        <Award class="size-8 text-white/15" />
                    </div>
                    <Badge
                        v-if="medal.visibility === 'private'"
                        variant="outline"
                        class="absolute top-2 right-2 border-white/15 bg-fl-black/70 text-[10px] text-white/60"
                    >
                        Privada
                    </Badge>
                </div>
                <div class="p-3">
                    <p class="truncate text-sm font-medium text-white">
                        {{ medal.title }}
                    </p>
                    <p class="text-xs text-white/40">
                        {{
                            [medal.distance_label, medal.event_date]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>
                </div>
            </Link>
        </div>
    </div>
</template>
