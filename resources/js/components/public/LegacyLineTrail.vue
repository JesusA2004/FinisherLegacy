<script setup lang="ts">
/**
 * The Legacy Line as an actual page-spanning graphic, not just the local
 * `.legacy-line`/`.legacy-line-v` gradient bars each section uses on its
 * own. A single SVG stroke runs down the left gutter for the whole page
 * (Hero → Story → Medalla/Placa → Legacy Code → Profile → Timeline → CTA)
 * and draws itself in with scroll via `stroke-dasharray`/`stroke-dashoffset`
 * — the classic technique, tied to `window.scrollY`, not a CSS scaleY
 * trick. Mounted once by `Home.vue`, absolutely positioned against
 * `<main>` in `PublicLayout.vue` (given `position: relative` for exactly
 * this), so its height always matches the real page height.
 *
 * Deliberately subtle: a faint always-visible track plus a brighter
 * revealed segment and a small leading glow dot — a signature, not a
 * progress bar. Hidden below `sm` (no safe gutter on the smallest phones)
 * and skipped entirely under prefers-reduced-motion.
 */
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

const prefersReducedMotion = useReducedMotion();
const wrapperEl = useTemplateRef<HTMLElement>('wrapper');
const trackHeight = ref(0);
const progress = ref(0);
let rafId: number | null = null;
let resizeObserver: ResizeObserver | undefined;

function measure() {
    trackHeight.value = wrapperEl.value?.offsetHeight ?? 0;
}

function updateProgress() {
    rafId = null;

    const scrollable =
        document.documentElement.scrollHeight - window.innerHeight;
    progress.value =
        scrollable > 0
            ? Math.min(1, Math.max(0, window.scrollY / scrollable))
            : 0;
}

function onScroll() {
    if (rafId === null) {
        rafId = requestAnimationFrame(updateProgress);
    }
}

onMounted(() => {
    if (prefersReducedMotion.value) {
        return;
    }

    measure();
    updateProgress();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });

    if (wrapperEl.value) {
        resizeObserver = new ResizeObserver(() => {
            measure();
            updateProgress();
        });
        resizeObserver.observe(wrapperEl.value);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', onScroll);
    resizeObserver?.disconnect();

    if (rafId !== null) {
        cancelAnimationFrame(rafId);
    }
});

const dashOffset = computed(() => trackHeight.value * (1 - progress.value));
const dotY = computed(() => trackHeight.value * progress.value);
const dotVisible = computed(
    () => progress.value > 0.01 && progress.value < 0.999,
);
</script>

<template>
    <div
        ref="wrapper"
        aria-hidden="true"
        class="pointer-events-none absolute inset-y-0 left-2 z-0 hidden w-5 sm:block md:left-3 lg:left-5"
    >
        <svg
            v-if="trackHeight > 0"
            width="20"
            :height="trackHeight"
            :viewBox="`0 0 20 ${trackHeight}`"
            preserveAspectRatio="none"
            class="overflow-visible"
        >
            <!-- Faint always-visible track -->
            <line
                x1="10"
                y1="0"
                x2="10"
                :y2="trackHeight"
                stroke="var(--fl-gold-dim)"
                stroke-width="1.5"
                opacity="0.12"
            />
            <!-- Revealed segment, drawn in with scroll -->
            <line
                v-if="!prefersReducedMotion"
                x1="10"
                y1="0"
                x2="10"
                :y2="trackHeight"
                stroke="var(--fl-gold)"
                stroke-width="1.5"
                opacity="0.55"
                :stroke-dasharray="trackHeight"
                :stroke-dashoffset="dashOffset"
            />
            <!-- Leading glow -->
            <circle
                v-if="!prefersReducedMotion && dotVisible"
                cx="10"
                :cy="dotY"
                r="3"
                fill="var(--fl-gold-soft)"
                style="filter: drop-shadow(0 0 4px var(--fl-gold))"
            />
        </svg>
    </div>
</template>
