<script setup lang="ts">
/**
 * Editorial poster, not a SaaS card: name lives on the photo, date is huge,
 * image and text drift at different speeds on hover for a subtle parallax
 * (brand system §21 / §23.11 Escena 08).
 */
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, MapPin } from '@lucide/vue';
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
        class="group relative flex aspect-[4/5] flex-col overflow-hidden rounded-2xl border border-white/10 bg-fl-graphite/60 transition-colors duration-300 hover:border-fl-gold/40 hover:shadow-[0_0_40px_-12px_rgba(207,171,89,0.4)]"
    >
        <!-- Photo layer — moves slower/further than the text on hover -->
        <div
            class="fl-shine absolute inset-0 overflow-hidden bg-gradient-to-br from-fl-graphite-light via-fl-graphite to-fl-black"
        >
            <img
                v-if="edition.event.cover_url"
                :src="edition.event.cover_url"
                :alt="edition.event.name"
                loading="lazy"
                class="size-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
            />
            <img
                v-else-if="placeholderExists"
                src="/media/home/events/event-placeholder.webp"
                alt=""
                loading="lazy"
                class="size-full object-cover opacity-70 transition-transform duration-700 ease-out group-hover:scale-110"
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

            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-fl-black via-fl-black/30 to-transparent"
            />
        </div>

        <!-- Huge date, top-left -->
        <div
            class="legacy-numeric relative flex items-baseline gap-2 p-5 leading-none text-white"
        >
            <span class="text-5xl font-black tracking-tight">{{
                dayNumber
            }}</span>
            <span
                class="text-sm font-semibold tracking-[0.2em] text-fl-gold-soft uppercase"
                >{{ monthAbbrev }}</span
            >
        </div>

        <Badge
            variant="outline"
            class="absolute top-5 right-5 backdrop-blur-sm"
            :class="phaseCopy[edition.phase].class"
        >
            {{ phaseCopy[edition.phase].label }}
        </Badge>

        <!-- Text layer — moves less than the photo, the parallax delta -->
        <div
            class="relative mt-auto flex flex-col gap-2 p-5 transition-transform duration-300 ease-out group-hover:-translate-y-1"
        >
            <span
                class="text-xs font-semibold tracking-[0.2em] text-fl-gold-soft uppercase"
            >
                {{ edition.event.sport.name }}
            </span>

            <h3
                class="text-xl leading-tight font-bold text-white transition-colors group-hover:text-fl-gold-soft"
            >
                {{ edition.event.name }}
            </h3>

            <div class="flex items-center gap-1.5 text-sm text-white/70">
                <MapPin class="size-3.5 shrink-0" />
                <span class="truncate">{{ edition.city }}</span>
                <span aria-hidden="true">·</span>
                <span class="capitalize">{{ formattedDate }}</span>
            </div>

            <div
                v-if="edition.distances.length"
                class="mt-1 flex flex-wrap gap-1.5"
            >
                <span
                    v-for="distance in edition.distances"
                    :key="distance"
                    class="rounded-full border border-white/20 bg-fl-black/40 px-2.5 py-0.5 text-xs text-white/70 backdrop-blur-sm"
                >
                    {{ distance }}
                </span>
            </div>
        </div>

        <!-- Circular arrow, poster signature detail -->
        <span
            class="absolute right-5 bottom-5 flex size-10 items-center justify-center rounded-full border border-fl-gold-soft/40 bg-fl-black/60 text-fl-gold-soft backdrop-blur-sm transition-transform duration-300 group-hover:scale-110 group-hover:bg-fl-gold-soft group-hover:text-fl-black"
        >
            <ArrowUpRight class="size-4" />
        </span>
    </Link>
</template>
