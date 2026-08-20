<script setup lang="ts">
import { QrCode } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

withDefaults(
    defineProps<{
        eventName?: string;
        athleteName?: string;
        time?: string;
        serial?: string;
    }>(),
    {
        eventName: 'TU EVENTO AQUÍ',
        athleteName: 'TU NOMBRE',
        time: '00:00:00',
        serial: '#FL-0000000',
    },
);

// Tilt-on-hover + a small "GIRA" label that follows the cursor, standing in
// for the light-catching reflection a real metal plate has (brand system
// §14 / §23.7 contextual cursor, scoped to just this element rather than a
// global custom cursor). Desktop only, off entirely under
// prefers-reduced-motion.
const prefersReducedMotion = useReducedMotion();
const plateEl = useTemplateRef<HTMLElement>('plate');
const hovering = ref(false);
const cursorX = ref(0);
const cursorY = ref(0);

function handlePointerMove(event: PointerEvent) {
    if (prefersReducedMotion.value || !plateEl.value) {
        return;
    }

    const rect = plateEl.value.getBoundingClientRect();
    const relX = (event.clientX - rect.left) / rect.width;
    const relY = (event.clientY - rect.top) / rect.height;

    plateEl.value.style.setProperty('--tilt-x', `${(relY - 0.5) * -6}deg`);
    plateEl.value.style.setProperty('--tilt-y', `${(relX - 0.5) * 6}deg`);
    plateEl.value.style.setProperty('--glare-x', `${relX * 100}%`);
    plateEl.value.style.setProperty('--glare-y', `${relY * 100}%`);
    cursorX.value = event.clientX - rect.left;
    cursorY.value = event.clientY - rect.top;
}

function resetTilt() {
    plateEl.value?.style.setProperty('--tilt-x', '0deg');
    plateEl.value?.style.setProperty('--tilt-y', '0deg');
    hovering.value = false;
}

const hotspots = [
    { label: 'Nombre', top: '38%', left: '38%' },
    { label: 'Tiempo', top: '58%', left: '38%' },
    { label: 'Legacy Code', top: '82%', left: '86%' },
];
</script>

<template>
    <div class="mx-auto w-full max-w-md" style="perspective: 1000px">
        <div
            ref="plate"
            class="fl-shine relative aspect-[3/2] w-full cursor-none rounded-xl border border-white/15 p-6 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.7)] transition-transform duration-300 ease-out"
            style="
                --tilt-x: 0deg;
                --tilt-y: 0deg;
                --glare-x: 50%;
                --glare-y: 30%;
                transform: rotateX(var(--tilt-x)) rotateY(var(--tilt-y));
                transform-style: preserve-3d;
                background:
                    repeating-linear-gradient(
                        100deg,
                        rgba(255, 255, 255, 0.05) 0px,
                        rgba(255, 255, 255, 0.05) 1px,
                        transparent 1px,
                        transparent 3px
                    ),
                    radial-gradient(
                        circle at var(--glare-x) var(--glare-y),
                        rgba(255, 255, 255, 0.14),
                        transparent 45%
                    ),
                    linear-gradient(
                        160deg,
                        #3a3a3d 0%,
                        #232326 55%,
                        #17171a 100%
                    );
            "
            @pointermove="handlePointerMove"
            @pointerenter="hovering = true"
            @pointerleave="resetTilt"
        >
            <div
                class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-br from-white/10 via-transparent to-transparent"
            />

            <div class="relative flex h-full flex-col justify-between">
                <div>
                    <p
                        class="text-[10px] font-semibold tracking-[0.3em] text-fl-gold-soft uppercase"
                    >
                        Finisher · Legacy
                    </p>
                    <p
                        class="mt-3 text-lg leading-tight font-bold tracking-tight text-white uppercase"
                    >
                        {{ eventName }}
                    </p>
                    <p class="mt-1 text-sm text-white/60">{{ athleteName }}</p>
                    <p
                        class="legacy-numeric mt-2 text-xl font-semibold text-fl-gold-soft"
                    >
                        {{ time }}
                    </p>
                </div>

                <div class="flex items-end justify-between">
                    <span
                        class="legacy-numeric font-mono text-[10px] text-white/50"
                        >{{ serial }}</span
                    >
                    <div
                        class="flex size-9 items-center justify-center rounded-md border border-fl-gold-soft/20 bg-black/30"
                    >
                        <QrCode class="size-5 text-fl-gold-soft" />
                    </div>
                </div>
            </div>

            <!-- Hotspots: hover to see what each field means -->
            <div
                v-for="hotspot in hotspots"
                :key="hotspot.label"
                class="group absolute z-10 flex size-5 -translate-x-1/2 -translate-y-1/2 items-center justify-center"
                :style="{ top: hotspot.top, left: hotspot.left }"
            >
                <span
                    class="flex size-4 items-center justify-center rounded-full border border-fl-gold-soft/50 bg-fl-black/70 text-[10px] leading-none text-fl-gold-soft backdrop-blur-sm transition-transform duration-200 group-hover:scale-125"
                    >+</span
                >
                <span
                    class="pointer-events-none absolute bottom-full left-1/2 mb-1.5 -translate-x-1/2 rounded-md border border-white/10 bg-fl-black px-2 py-1 text-[10px] font-medium tracking-wide whitespace-nowrap text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100"
                >
                    {{ hotspot.label }}
                </span>
            </div>

            <!-- Cursor-follow "GIRA" label -->
            <span
                v-if="hovering && !prefersReducedMotion"
                class="pointer-events-none absolute z-20 rounded-full bg-fl-gold-soft px-2.5 py-1 text-[10px] font-bold tracking-wide text-fl-black uppercase"
                :style="{
                    top: `${cursorY}px`,
                    left: `${cursorX}px`,
                    transform: 'translate(-50%, -150%)',
                }"
            >
                Explora
            </span>
        </div>

        <p
            class="mt-3 text-center text-xs tracking-wide text-white/40 uppercase"
        >
            Interactúa con la placa
        </p>
    </div>
</template>
