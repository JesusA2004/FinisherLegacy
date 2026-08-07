<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Award, Lock, MapPin, QrCode } from '@lucide/vue';
import { computed } from 'vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import { Button } from '@/components/ui/button';
import type { LegacyCodeAthlete, LegacyCodePlate } from '@/types';

const { code, available, linked, plate, athlete } = defineProps<{
    code: string;
    available: boolean;
    linked: boolean;
    plate: LegacyCodePlate | null;
    athlete: LegacyCodeAthlete | null;
}>();

const formattedDate = computed(() => {
    if (!plate?.event_date) {
        return null;
    }

    return new Date(`${plate.event_date}T00:00:00`).toLocaleDateString(
        'es-MX',
        { day: 'numeric', month: 'long', year: 'numeric' },
    );
});

const initials = computed(() =>
    (plate?.athlete_name ?? 'FL')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <Head :title="`Legacy Code ${code}`" />

    <section class="mx-auto max-w-lg px-4 py-16 sm:px-6 lg:px-8">
        <div class="mb-8 flex justify-center">
            <FinisherLegacyLogo size="sm" />
        </div>

        <template v-if="!available">
            <div
                class="rounded-2xl border border-white/10 bg-fl-graphite/50 p-8 text-center"
            >
                <div
                    class="mx-auto flex size-16 items-center justify-center rounded-full border border-white/15 bg-fl-black text-white/50"
                >
                    <Lock class="size-7" />
                </div>
                <p
                    class="mt-6 rounded-md border border-white/10 bg-fl-black px-3 py-1.5 font-mono text-sm text-white/50"
                >
                    {{ code }}
                </p>
                <h1 class="mt-6 text-2xl font-bold text-white">
                    Este Legacy Code no está disponible
                </h1>
                <p class="mt-3 text-sm text-white/60">
                    Si crees que esto es un error, contáctanos y con gusto lo
                    revisamos.
                </p>
            </div>
        </template>

        <template v-else-if="!plate">
            <div
                class="rounded-2xl border border-white/10 bg-fl-graphite/50 p-8 text-center"
            >
                <div
                    class="mx-auto flex size-16 items-center justify-center rounded-full border border-fl-gold/30 bg-fl-black text-fl-gold"
                >
                    <QrCode class="size-7" />
                </div>
                <p
                    class="mt-6 rounded-md border border-white/10 bg-fl-black px-3 py-1.5 font-mono text-sm text-fl-gold"
                >
                    {{ code }}
                </p>
                <h1 class="mt-6 text-2xl font-bold text-white">
                    Este Legacy Code aún no tiene una placa asignada
                </h1>
                <p class="mt-3 text-sm text-white/60">
                    Vuelve a intentarlo más tarde.
                </p>
            </div>
        </template>

        <template v-else>
            <!-- Athlete card -->
            <div
                class="flex items-center gap-4 rounded-2xl border border-white/10 bg-fl-graphite/50 p-5"
            >
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-full border-2 border-fl-gold/40 bg-fl-black text-base font-semibold text-fl-gold"
                >
                    {{ initials }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-white">
                        {{ plate.athlete_name ?? 'Placa Finisher Legacy' }}
                    </p>
                    <p
                        v-if="athlete"
                        class="flex items-center gap-1 text-sm text-white/50"
                    >
                        <span v-if="athlete.sport">{{ athlete.sport }}</span>
                        <span v-if="athlete.sport && athlete.city">·</span>
                        <span
                            v-if="athlete.city"
                            class="inline-flex items-center gap-1"
                        >
                            <MapPin class="size-3" />{{ athlete.city }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Big gold result card -->
            <div
                class="mt-4 rounded-2xl border-2 border-fl-gold/40 bg-gradient-to-b from-fl-gold/10 to-fl-graphite/40 p-6"
            >
                <p
                    v-if="plate.event_name"
                    class="text-xl leading-tight font-bold text-white"
                >
                    {{ plate.event_name }}
                </p>
                <p
                    v-if="formattedDate"
                    class="mt-1 text-sm text-white/50 capitalize"
                >
                    {{ formattedDate }}
                </p>

                <p
                    v-if="plate.official_time"
                    class="mt-5 font-mono text-4xl font-bold text-fl-gold"
                >
                    {{ plate.official_time }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-if="plate.race_name"
                        class="rounded-full border border-white/15 bg-fl-black px-3 py-1 text-xs font-medium text-white/70"
                    >
                        {{ plate.race_name }}
                    </span>
                    <span
                        v-if="plate.pace"
                        class="rounded-full border border-white/15 bg-fl-black px-3 py-1 text-xs font-medium text-white/70"
                    >
                        Ritmo {{ plate.pace }}
                    </span>
                </div>
            </div>

            <div
                class="mt-4 rounded-xl border border-dashed border-white/15 p-5 text-center"
            >
                <template v-if="linked">
                    <Award class="mx-auto size-5 text-fl-gold" />
                    <p class="mt-2 text-sm text-white/70">
                        Esta placa ya forma parte de un Legacy Profile.
                    </p>
                </template>
                <template v-else>
                    <p class="text-sm text-white/70">
                        Esta placa aún no ha sido vinculada a un Legacy.
                    </p>
                    <Button
                        disabled
                        class="mt-4 w-full bg-fl-gold/40 text-fl-black/70"
                    >
                        Vincular a mi Legacy — Próximamente
                    </Button>
                </template>
            </div>
        </template>
    </section>
</template>
