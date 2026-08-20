<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useAssetExists } from '@/composables/useAssetProbe';
import { show as eventShow } from '@/routes/events';
import type { EventEditionCard, EventPhase } from '@/types';

const { edition } = defineProps<{
    edition: EventEditionCard;
}>();

// cover_url → brand placeholder photo (checked before render, never an
// optimistic-then-@error flash) → typographic fallback. See
// public/media/home/events/README.md.
const { exists: placeholderExists } = useAssetExists(
    '/media/home/events/event-placeholder.webp',
);

const phaseCopy: Record<EventPhase, { label: string; class: string }> = {
    upcoming: {
        label: 'Próximo',
        class: 'bg-fl-gold/15 text-fl-gold-soft border-fl-gold/30',
    },
    ongoing: {
        label: 'En curso',
        class: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    },
    finished: {
        label: 'Finalizado',
        class: 'bg-white/10 text-white/60 border-white/15',
    },
};

const eventDate = computed(() => new Date(`${edition.event_date}T00:00:00`));

const formattedDate = computed(() =>
    eventDate.value.toLocaleDateString('es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }),
);

const dayNumber = computed(() =>
    eventDate.value.toLocaleDateString('es-MX', { day: 'numeric' }),
);

const monthAbbrev = computed(() =>
    eventDate.value
        .toLocaleDateString('es-MX', { month: 'short' })
        .replace('.', ''),
);
</script>

<template>
    <Link
        :href="eventShow(edition.event.slug)"
        class="group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-fl-graphite/60 transition-all duration-300 hover:-translate-y-1 hover:border-fl-gold/40 hover:shadow-[0_0_40px_-12px_rgba(207,171,89,0.4)]"
    >
        <div
            class="fl-shine relative aspect-16/9 w-full overflow-hidden bg-gradient-to-br from-fl-graphite-light via-fl-graphite to-fl-black"
        >
            <img
                v-if="edition.event.cover_url"
                :src="edition.event.cover_url"
                :alt="edition.event.name"
                loading="lazy"
                class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <img
                v-else-if="placeholderExists"
                src="/media/home/events/event-placeholder.webp"
                alt=""
                loading="lazy"
                class="size-full object-cover opacity-70 transition-transform duration-500 group-hover:scale-105"
            />
            <div
                v-else
                class="flex size-full items-center justify-center"
                style="
                    background-image: repeating-linear-gradient(
                        100deg,
                        rgba(255, 255, 255, 0.04) 0px,
                        rgba(255, 255, 255, 0.04) 1px,
                        transparent 1px,
                        transparent 4px
                    );
                "
            >
                <span
                    class="text-4xl font-black tracking-tight text-white/10 uppercase"
                >
                    {{ edition.event.sport.name }}
                </span>
            </div>

            <Badge
                variant="outline"
                class="absolute top-3 left-3 backdrop-blur-sm"
                :class="phaseCopy[edition.phase].class"
            >
                {{ phaseCopy[edition.phase].label }}
            </Badge>

            <div
                class="legacy-numeric absolute top-3 right-3 flex flex-col items-center rounded-lg border border-white/10 bg-fl-black/80 px-2.5 py-1.5 leading-none backdrop-blur-sm"
            >
                <span class="text-lg font-bold text-white">{{
                    dayNumber
                }}</span>
                <span
                    class="mt-0.5 text-[10px] font-semibold tracking-wide text-fl-gold-soft uppercase"
                    >{{ monthAbbrev }}</span
                >
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-3 p-5">
            <span
                class="text-xs font-semibold tracking-[0.2em] text-fl-gold-soft uppercase"
            >
                {{ edition.event.sport.name }}
            </span>

            <h3
                class="text-lg font-semibold text-white transition-colors group-hover:text-fl-gold-soft"
            >
                {{ edition.event.name }}
            </h3>

            <div class="flex items-center gap-1.5 text-sm text-white/70">
                <MapPin class="size-3.5 shrink-0" />
                <span class="truncate">{{ edition.city }}</span>
                <span aria-hidden="true">·</span>
                <span class="capitalize">{{ formattedDate }}</span>
            </div>

            <div v-if="edition.distances.length" class="flex flex-wrap gap-1.5">
                <span
                    v-for="distance in edition.distances"
                    :key="distance"
                    class="rounded-full border border-white/15 px-2.5 py-0.5 text-xs text-white/60"
                >
                    {{ distance }}
                </span>
            </div>

            <div
                class="mt-auto flex items-center gap-1.5 pt-2 text-sm font-medium text-fl-gold-soft"
            >
                Ver evento
                <ArrowRight
                    class="size-3.5 transition-transform group-hover:translate-x-1"
                />
            </div>
        </div>
    </Link>
</template>
