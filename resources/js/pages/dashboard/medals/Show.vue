<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Award, Pencil, QrCode, Trash2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { confirmDestructive } from '@/lib/swal';
import { dashboard } from '@/routes';
import { destroy, edit, index } from '@/routes/dashboard/medals';
import { show as legacyCodeShow } from '@/routes/legacy-code';
import type { MedalDetail } from '@/types';

const { medal } = defineProps<{
    medal: MedalDetail;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Mis Medallas', href: index() },
            { title: medal.title, href: '#' },
        ],
    },
});

async function handleDelete() {
    const result = await confirmDestructive({
        title: '¿Archivar esta medalla?',
        text: 'Se ocultará de tu colección. Esta acción no borra permanentemente tu historial ni afecta placas ya vinculadas.',
        confirmButtonText: 'Sí, archivar',
    });

    if (result.isConfirmed) {
        router.delete(destroy(medal.id).url);
    }
}
</script>

<template>
    <Head :title="medal.title" />

    <div class="mx-auto max-w-4xl p-4 md:p-6">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-white">
                        {{ medal.title }}
                    </h1>
                    <Badge
                        v-if="medal.is_official"
                        variant="outline"
                        class="border-fl-gold/30 text-fl-gold"
                    >
                        Resultado oficial
                    </Badge>
                    <Badge
                        v-if="medal.visibility === 'private'"
                        variant="outline"
                        class="border-white/15 text-white/50"
                    >
                        Privada
                    </Badge>
                </div>
                <p
                    v-if="medal.event_name || medal.race_name"
                    class="mt-1 text-sm text-white/50"
                >
                    {{
                        [medal.event_name, medal.race_name]
                            .filter(Boolean)
                            .join(' · ')
                    }}
                </p>
            </div>

            <div class="flex shrink-0 gap-2">
                <Button
                    as-child
                    variant="outline"
                    size="icon"
                    class="border-white/15 text-white hover:bg-white/10 hover:text-white"
                    aria-label="Editar medalla"
                >
                    <Link :href="edit(medal.id)">
                        <Pencil class="size-4" />
                    </Link>
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    class="border-white/15 text-red-400 hover:bg-red-500/10 hover:text-red-400"
                    aria-label="Archivar medalla"
                    @click="handleDelete"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div
                class="overflow-hidden rounded-2xl border border-white/10 bg-fl-graphite/40"
            >
                <div
                    class="flex items-center justify-between px-4 py-2 text-xs text-white/40 uppercase"
                >
                    Frente
                </div>
                <div class="aspect-square bg-fl-black">
                    <img
                        v-if="medal.front_image_url"
                        :src="medal.front_image_url"
                        :alt="`${medal.title} — frente`"
                        class="size-full object-cover"
                    />
                    <div
                        v-else
                        class="flex size-full items-center justify-center"
                    >
                        <Award class="size-10 text-white/15" />
                    </div>
                </div>
            </div>

            <div
                v-if="medal.back_image_url"
                class="overflow-hidden rounded-2xl border border-white/10 bg-fl-graphite/40"
            >
                <div class="px-4 py-2 text-xs text-white/40 uppercase">
                    Reverso
                </div>
                <div class="aspect-square bg-fl-black">
                    <img
                        :src="medal.back_image_url"
                        :alt="`${medal.title} — reverso`"
                        class="size-full object-cover"
                    />
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 sm:grid-cols-3">
            <div
                v-if="medal.event_date"
                class="rounded-xl border border-white/10 bg-fl-graphite/40 p-4"
            >
                <p class="text-xs text-white/40 uppercase">Fecha</p>
                <p class="mt-1 font-medium text-white">
                    {{ medal.event_date }}
                </p>
            </div>
            <div
                v-if="medal.official_time"
                class="rounded-xl border border-white/10 bg-fl-graphite/40 p-4"
            >
                <p class="text-xs text-white/40 uppercase">Tiempo</p>
                <p class="mt-1 font-mono font-medium text-fl-gold">
                    {{ medal.official_time }}
                </p>
            </div>
            <div
                v-if="medal.pace"
                class="rounded-xl border border-white/10 bg-fl-graphite/40 p-4"
            >
                <p class="text-xs text-white/40 uppercase">Ritmo</p>
                <p class="mt-1 font-medium text-white">{{ medal.pace }}</p>
            </div>
        </div>

        <div v-if="medal.gallery_images.length" class="mt-6">
            <h2
                class="mb-3 text-sm font-semibold tracking-wide text-white/60 uppercase"
            >
                Galería
            </h2>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                <div
                    v-for="image in medal.gallery_images"
                    :key="image.id"
                    class="aspect-square overflow-hidden rounded-xl border border-white/10 bg-fl-black"
                >
                    <img
                        v-if="image.url"
                        :src="image.url"
                        alt="Foto de la galería"
                        class="size-full object-cover"
                    />
                </div>
            </div>
        </div>

        <div v-if="medal.story" class="mt-6">
            <h2
                class="text-sm font-semibold tracking-wide text-white/60 uppercase"
            >
                Mi historia
            </h2>
            <p class="mt-2 leading-relaxed whitespace-pre-line text-white/70">
                {{ medal.story }}
            </p>
        </div>

        <div
            v-if="medal.legacy_code"
            class="mt-6 flex items-center justify-between rounded-xl border border-fl-gold/20 bg-fl-gold/5 p-4"
        >
            <div class="flex items-center gap-3">
                <QrCode class="size-5 text-fl-gold" />
                <div>
                    <p class="text-sm font-medium text-white">
                        Legacy Code vinculado
                    </p>
                    <p class="font-mono text-xs text-white/50">
                        {{ medal.legacy_code.code }}
                    </p>
                </div>
            </div>
            <Button
                as-child
                variant="outline"
                size="sm"
                class="border-fl-gold/30 text-fl-gold hover:bg-fl-gold/10"
            >
                <Link :href="legacyCodeShow(medal.legacy_code.code)">Ver</Link>
            </Button>
        </div>
    </div>
</template>
