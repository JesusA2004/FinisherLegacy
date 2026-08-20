<script setup lang="ts">
/**
 * MEDALLA → PLACA → LEGACY CODE → LEGACY PROFILE, shown as a connected
 * sequence instead of four disconnected icons (brand system §13). The
 * connector is the Legacy Line motif: copper for the physical steps, ice
 * for the digital ones. Desktop renders an icon stepper row (fixed column
 * count, so the connector can safely be a flex-1 line between fixed icon
 * cells) with a separate, perfectly aligned text grid below it. Mobile
 * stacks each step as one vertical unit — icon, connector, title, text.
 */
import { Award, IdCard, Medal, QrCode } from '@lucide/vue';
import Reveal from '@/components/motion/Reveal.vue';

const states = [
    {
        number: '01',
        icon: Medal,
        accent: 'copper' as const,
        title: 'Medalla',
        description: 'El logro comienza aquí.',
    },
    {
        number: '02',
        icon: Award,
        accent: 'copper' as const,
        title: 'Placa',
        description:
            'Convertimos los datos de esa carrera en una pieza creada para permanecer.',
    },
    {
        number: '03',
        icon: QrCode,
        accent: 'ice' as const,
        title: 'Legacy Code',
        description:
            'Una conexión permanente entre el objeto físico y su historia digital.',
    },
    {
        number: '04',
        icon: IdCard,
        accent: 'ice' as const,
        title: 'Legacy Profile',
        description: 'Cada carrera pasa a formar parte de tu historia como atleta.',
    },
];
</script>

<template>
    <div>
        <!-- Desktop: icon stepper row, connectors fill the space between
             fixed-size icon cells so the column count never shifts. -->
        <div class="hidden items-center lg:flex">
            <template v-for="(state, index) in states" :key="`icon-${state.title}`">
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-full border bg-legacy-carbon/60"
                    :class="
                        state.accent === 'ice'
                            ? 'border-legacy-ice/30 text-legacy-ice'
                            : 'border-legacy-copper/40 text-legacy-copper-soft'
                    "
                >
                    <component :is="state.icon" class="size-6" />
                </div>
                <div v-if="index < states.length - 1" class="legacy-line mx-3 flex-1" />
            </template>
        </div>

        <div class="mt-6 hidden grid-cols-4 gap-8 lg:grid">
            <Reveal
                v-for="(state, index) in states"
                :key="`text-${state.title}`"
                :delay-ms="index * 90"
                class="flex flex-col gap-2"
            >
                <span class="legacy-numeric text-sm font-semibold text-legacy-titanium/40">
                    {{ state.number }}
                </span>
                <h3 class="text-lg font-semibold text-legacy-bone">{{ state.title }}</h3>
                <p class="text-sm leading-relaxed text-legacy-titanium">
                    {{ state.description }}
                </p>
            </Reveal>
        </div>

        <!-- Mobile: one vertical unit per step. -->
        <div class="flex flex-col lg:hidden">
            <Reveal
                v-for="(state, index) in states"
                :key="`mobile-${state.title}`"
                :delay-ms="index * 90"
                class="flex flex-col items-start gap-3"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-12 shrink-0 items-center justify-center rounded-full border bg-legacy-carbon/60"
                        :class="
                            state.accent === 'ice'
                                ? 'border-legacy-ice/30 text-legacy-ice'
                                : 'border-legacy-copper/40 text-legacy-copper-soft'
                        "
                    >
                        <component :is="state.icon" class="size-5" />
                    </div>
                    <span class="legacy-numeric text-sm font-semibold text-legacy-titanium/40">
                        {{ state.number }}
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-legacy-bone">{{ state.title }}</h3>
                <p class="pb-2 text-sm leading-relaxed text-legacy-titanium">
                    {{ state.description }}
                </p>
                <div
                    v-if="index < states.length - 1"
                    class="legacy-line-v mb-2 h-6 self-start"
                    aria-hidden="true"
                />
            </Reveal>
        </div>
    </div>
</template>
