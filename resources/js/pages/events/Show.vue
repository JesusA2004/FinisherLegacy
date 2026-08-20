<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, MapPin, Trophy } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { preregister } from '@/routes/events';
import type { EventDetail, EventEditionDetail } from '@/types';

const { event, edition } = defineProps<{
    event: EventDetail;
    edition: EventEditionDetail | null;
}>();

const formattedDate = computed(() => {
    if (!edition) {
        return null;
    }

    return new Date(`${edition.event_date}T00:00:00`).toLocaleDateString(
        'es-MX',
        { day: 'numeric', month: 'long', year: 'numeric' },
    );
});

const phaseCopy: Record<string, string> = {
    upcoming: 'Próximo',
    ongoing: 'En curso',
    finished: 'Finalizado',
};
</script>

<template>
    <Head :title="event.name">
        <meta
            name="description"
            :content="event.description ?? `${event.name} — Finisher Legacy`"
        />
        <meta property="og:title" :content="event.name" />
    </Head>

    <section class="relative border-b border-white/5">
        <div
            class="relative flex h-72 items-end overflow-hidden bg-gradient-to-br from-legacy-carbon-lift via-legacy-carbon to-legacy-ink sm:h-96"
        >
            <img
                v-if="event.cover_url"
                :src="event.cover_url"
                :alt="event.name"
                class="absolute inset-0 size-full object-cover opacity-60"
            />
            <div
                class="absolute inset-0 bg-gradient-to-t from-legacy-ink via-legacy-ink/40 to-transparent"
            />

            <div
                class="relative mx-auto w-full max-w-6xl px-4 pb-10 sm:px-6 lg:px-8"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        class="border-legacy-copper/30 bg-legacy-ink/60 text-legacy-copper-soft"
                    >
                        {{ event.sport }}
                    </Badge>
                    <Badge
                        v-if="edition"
                        variant="outline"
                        class="border-legacy-titanium/15 bg-legacy-ink/60 text-legacy-titanium"
                    >
                        {{ phaseCopy[edition.phase] }}
                    </Badge>
                </div>
                <h1
                    class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-5xl"
                >
                    {{ event.name }}
                </h1>
                <p v-if="event.organizer" class="mt-2 text-sm text-white/50">
                    Organizado por {{ event.organizer }}
                </p>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div
            class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-3 lg:px-8"
        >
            <div class="space-y-10 lg:col-span-2">
                <div v-if="event.description">
                    <h2 class="text-lg font-semibold text-white">
                        Sobre el evento
                    </h2>
                    <p
                        class="mt-3 leading-relaxed whitespace-pre-line text-white/60"
                    >
                        {{ event.description }}
                    </p>
                </div>

                <div v-if="edition">
                    <h2 class="text-lg font-semibold text-white">Distancias</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="race in edition.races"
                            :key="race.name"
                            class="flex items-center justify-between rounded-xl border border-white/10 bg-legacy-carbon/40 px-4 py-3"
                        >
                            <span class="font-medium text-white">{{
                                race.name
                            }}</span>
                            <span
                                v-if="race.start_time"
                                class="text-sm text-white/40"
                            >
                                {{ race.start_time }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-xl border border-white/10 bg-legacy-carbon/40 p-6 text-legacy-titanium/60"
                >
                    Este evento todavía no tiene una edición publicada con fecha
                    activa.
                </div>
            </div>

            <aside class="space-y-6">
                <div
                    v-if="edition"
                    class="space-y-5 rounded-2xl border border-white/10 bg-legacy-carbon/50 p-6"
                >
                    <div class="flex items-start gap-3">
                        <Calendar class="mt-0.5 size-4 shrink-0 text-legacy-copper-soft" />
                        <div>
                            <p class="text-xs text-white/40">Fecha</p>
                            <p
                                class="text-sm font-medium text-white capitalize"
                            >
                                {{ formattedDate }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <MapPin class="mt-0.5 size-4 shrink-0 text-legacy-copper-soft" />
                        <div>
                            <p class="text-xs text-white/40">Ubicación</p>
                            <p class="text-sm font-medium text-white">
                                {{ edition.city }}, {{ edition.country }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <Trophy class="mt-0.5 size-4 shrink-0 text-legacy-copper-soft" />
                        <div>
                            <p class="text-xs text-white/40">Edición</p>
                            <p class="text-sm font-medium text-white">
                                {{ edition.name }} · {{ edition.year }}
                            </p>
                        </div>
                    </div>

                    <Button
                        v-if="edition.phase !== 'finished'"
                        as-child
                        size="lg"
                        class="w-full bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                    >
                        <Link :href="preregister(event.slug)">
                            PRERREGISTRARME
                        </Link>
                    </Button>
                </div>
            </aside>
        </div>
    </section>
</template>
