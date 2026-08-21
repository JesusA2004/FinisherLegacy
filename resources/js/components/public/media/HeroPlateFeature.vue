<script setup lang="ts">
/**
 * The Legacy Plate as the Hero's second visual layer — on top of the
 * cinematic video, not competing with it for the whole frame. Wide desktop
 * only (xl+, 1280px+): below that the H1 needs the full width and meets
 * the plate properly a few sections down (StickyLegacyJourney, PlateMedia).
 * Reuses
 * PlateShowcase.vue itself so the geometry — ribbon, lateral loops, brushed
 * metal — never drifts from the product shown later in the page; hotspots
 * and the flip caption are switched off here since this is atmosphere, not
 * the interactive showcase.
 */
import { useTemplateRef } from 'vue';
import PlateShowcase from '@/components/public/PlateShowcase.vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

const prefersReducedMotion = useReducedMotion();
const wrapperEl = useTemplateRef<HTMLElement>('wrapper');

function handlePointerMove(event: PointerEvent) {
    if (prefersReducedMotion.value || !wrapperEl.value) {
        return;
    }

    const relX = event.clientX / window.innerWidth - 0.5;
    const relY = event.clientY / window.innerHeight - 0.5;
    wrapperEl.value.style.setProperty('--parallax-x', `${relX * -14}px`);
    wrapperEl.value.style.setProperty('--parallax-y', `${relY * -10}px`);
}
</script>

<template>
    <div
        class="pointer-events-none absolute inset-0 hidden xl:block"
        @pointermove="handlePointerMove"
    >
        <div
            ref="wrapper"
            class="fl-hero-plate-in pointer-events-auto absolute top-1/2 right-[3%] w-[240px] -translate-y-1/2 2xl:right-[6%] 2xl:w-[280px]"
            style="
                --parallax-x: 0px;
                --parallax-y: 0px;
                transform: translateY(-50%)
                    translate(var(--parallax-x), var(--parallax-y))
                    rotate(-8deg);
                transition: transform 400ms cubic-bezier(0.16, 1, 0.3, 1);
            "
        >
            <PlateShowcase
                size="sm"
                :show-hotspots="false"
                :show-caption="false"
                event-name="Maratón CDMX"
                athlete-name="Tu nombre"
                time="03:42:18"
            />

            <!-- Medal glimpse hanging below — the plate is mid-ribbon, not
                 the whole story. -->
            <div
                aria-hidden="true"
                class="fl-hero-medal-glow relative mx-auto -mt-1 flex size-16 items-center justify-center rounded-full border border-fl-gold/30 xl:size-20"
                style="
                    background: radial-gradient(
                        circle at 35% 30%,
                        #3a3a3d,
                        #1a1a1c 70%
                    );
                "
            >
                <span
                    class="size-11 rounded-full border border-fl-gold-soft/25 xl:size-14"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.fl-hero-plate-in {
    animation: fl-hero-plate-in 900ms cubic-bezier(0.16, 1, 0.3, 1) 200ms both;
}

@keyframes fl-hero-plate-in {
    from {
        opacity: 0;
        transform: translateY(-45%) translate(0, 0) rotate(-8deg) scale(0.92);
    }
    to {
        opacity: 1;
    }
}

.fl-hero-medal-glow {
    box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.7);
}

@media (prefers-reduced-motion: reduce) {
    .fl-hero-plate-in {
        animation: none;
    }
}
</style>
