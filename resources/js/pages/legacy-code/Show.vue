<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Award, Lock, QrCode } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { LegacyCodePlate } from '@/types';

const { code, available, linked, plate } = defineProps<{
    code: string;
    available: boolean;
    linked: boolean;
    plate: LegacyCodePlate | null;
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
</script>

<template>
    <Head :title="`Legacy Code ${code}`" />

    <section class="mx-auto max-w-lg px-4 py-24 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl border border-white/10 bg-fl-graphite/50 p-8 text-center"
        >
            <div
                class="mx-auto flex size-16 items-center justify-center rounded-full border border-fl-gold/30 bg-fl-black text-fl-gold"
            >
                <Lock v-if="!available" class="size-7" />
                <QrCode v-else class="size-7" />
            </div>

            <p
                class="mt-6 rounded-md border border-white/10 bg-fl-black px-3 py-1.5 font-mono text-sm text-fl-gold"
            >
                {{ code }}
            </p>

            <template v-if="!available">
                <h1 class="mt-6 text-2xl font-bold text-white">
                    Este Legacy Code no está disponible
                </h1>
                <p class="mt-3 text-sm text-white/60">
                    Si crees que esto es un error, contáctanos y con gusto lo
                    revisamos.
                </p>
            </template>

            <template v-else-if="!plate">
                <h1 class="mt-6 text-2xl font-bold text-white">
                    Este Legacy Code aún no tiene una placa asignada
                </h1>
                <p class="mt-3 text-sm text-white/60">
                    Vuelve a intentarlo más tarde.
                </p>
            </template>

            <template v-else>
                <h1 class="mt-6 text-2xl font-bold text-white">
                    {{ plate.athlete_name ?? 'Placa Finisher Legacy' }}
                </h1>

                <div class="mt-6 space-y-3 text-left">
                    <div
                        v-if="plate.event_name"
                        class="flex items-center justify-between border-b border-white/5 pb-3"
                    >
                        <span class="text-sm text-white/40">Evento</span>
                        <span class="text-sm font-medium text-white">{{
                            plate.event_name
                        }}</span>
                    </div>
                    <div
                        v-if="plate.race_name"
                        class="flex items-center justify-between border-b border-white/5 pb-3"
                    >
                        <span class="text-sm text-white/40">Distancia</span>
                        <span class="text-sm font-medium text-white">{{
                            plate.race_name
                        }}</span>
                    </div>
                    <div
                        v-if="formattedDate"
                        class="flex items-center justify-between border-b border-white/5 pb-3"
                    >
                        <span class="text-sm text-white/40">Fecha</span>
                        <span
                            class="text-sm font-medium text-white capitalize"
                            >{{ formattedDate }}</span
                        >
                    </div>
                    <div
                        v-if="plate.official_time"
                        class="flex items-center justify-between border-b border-white/5 pb-3"
                    >
                        <span class="text-sm text-white/40">Tiempo</span>
                        <span class="text-sm font-medium text-white">{{
                            plate.official_time
                        }}</span>
                    </div>
                    <div
                        v-if="plate.pace"
                        class="flex items-center justify-between pb-1"
                    >
                        <span class="text-sm text-white/40">Ritmo</span>
                        <span class="text-sm font-medium text-white">{{
                            plate.pace
                        }}</span>
                    </div>
                </div>

                <div
                    class="mt-8 rounded-xl border border-dashed border-white/15 p-5"
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
        </div>
    </section>
</template>
