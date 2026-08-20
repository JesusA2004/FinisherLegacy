<script setup lang="ts">
import { QrCode } from '@lucide/vue';
import { useTemplateRef } from 'vue';
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

// A very small tilt-on-hover, standing in for the light-catching reflection
// a real metal plate has (brand system §14). Desktop only, off entirely
// under prefers-reduced-motion.
const prefersReducedMotion = useReducedMotion();
const plateEl = useTemplateRef<HTMLElement>('plate');

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
}

function resetTilt() {
    plateEl.value?.style.setProperty('--tilt-x', '0deg');
    plateEl.value?.style.setProperty('--tilt-y', '0deg');
}
</script>

<template>
    <div class="mx-auto w-full max-w-sm" style="perspective: 1000px">
        <div
            ref="plate"
            class="legacy-shine relative aspect-[3/2] w-full rounded-xl border border-legacy-titanium/15 p-6 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.7)] transition-transform duration-300 ease-out"
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
                    radial-gradient(circle at var(--glare-x) var(--glare-y), rgba(255,255,255,0.14), transparent 45%),
                    linear-gradient(160deg, #3a3a3d 0%, #232326 55%, #17171a 100%);
            "
            @pointermove="handlePointerMove"
            @pointerleave="resetTilt"
        >
            <div
                class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-br from-white/10 via-transparent to-transparent"
            />

            <div class="relative flex h-full flex-col justify-between">
                <div>
                    <p
                        class="text-[10px] font-semibold tracking-[0.3em] text-legacy-copper-soft uppercase"
                    >
                        Finisher · Legacy
                    </p>
                    <p
                        class="mt-3 text-lg leading-tight font-bold tracking-tight text-legacy-bone uppercase"
                    >
                        {{ eventName }}
                    </p>
                    <p class="mt-1 text-sm text-legacy-titanium">{{ athleteName }}</p>
                    <p class="legacy-numeric mt-2 text-xl font-semibold text-legacy-copper-soft">
                        {{ time }}
                    </p>
                </div>

                <div class="flex items-end justify-between">
                    <span class="legacy-numeric font-mono text-[10px] text-legacy-titanium/50">{{
                        serial
                    }}</span>
                    <div
                        class="flex size-9 items-center justify-center rounded-md border border-legacy-ice/20 bg-black/30"
                    >
                        <QrCode class="size-5 text-legacy-ice" />
                    </div>
                </div>
            </div>
        </div>

        <p class="mt-3 text-center text-xs tracking-wide text-legacy-titanium/40 uppercase">
            Interactúa con la placa
        </p>
    </div>
</template>
