<script setup lang="ts">
/**
 * Crossfading photo sequence for "NO ES SOLO UNA MEDALLA" (Escena 02).
 * Defaults to the CSS panel — always instant. Cycles through whichever
 * story photos useAssetExists confirms exist (checked once on mount, no
 * optimistic render-then-@error race — see HomeHeroMedia.vue for why that
 * pattern was flaky). See public/media/home/story/README.md.
 */
import { useIntersectionObserver } from '@vueuse/core';
import { onBeforeUnmount, ref, useTemplateRef } from 'vue';
import { useAssetsExisting } from '@/composables/useAssetProbe';
import { useReducedMotion } from '@/composables/useReducedMotion';

const SOURCES = [
    '/media/home/story/training-dawn.webp',
    '/media/home/story/race-effort.webp',
    '/media/home/story/finish-emotion.webp',
    '/media/home/story/medal-closeup.webp',
];

const { existing: available } = useAssetsExisting(SOURCES);
const prefersReducedMotion = useReducedMotion();
const activePos = ref(0);
const rootEl = useTemplateRef<HTMLElement>('root');
let timer: ReturnType<typeof setInterval> | undefined;

function startCycle() {
    if (timer || prefersReducedMotion.value || available.value.length < 2) {
        return;
    }

    timer = setInterval(() => {
        activePos.value = (activePos.value + 1) % available.value.length;
    }, 2600);
}

useIntersectionObserver(
    rootEl,
    ([entry]) => {
        if (entry?.isIntersecting) {
            startCycle();
        }
    },
    { threshold: 0.3 },
);

onBeforeUnmount(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <div
        ref="root"
        class="relative aspect-4/5 w-full overflow-hidden rounded-2xl border border-white/10 bg-fl-graphite/50"
    >
        <template v-if="available.length > 0">
            <img
                v-for="(src, pos) in available"
                :key="src"
                :src="src"
                alt=""
                loading="lazy"
                class="absolute inset-0 size-full object-cover transition-opacity duration-700 ease-in-out"
                :class="pos === activePos ? 'opacity-100' : 'opacity-0'"
            />
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-fl-black/60 via-transparent to-transparent"
            />
        </template>

        <!-- CSS fallback — never an empty gray box (brand system §23.12) -->
        <div v-else class="absolute inset-0">
            <div
                class="absolute inset-0"
                style="
                    background:
                        radial-gradient(
                            ellipse 80% 60% at 30% 20%,
                            color-mix(in srgb, var(--fl-gold) 16%, transparent),
                            transparent 65%
                        ),
                        linear-gradient(
                            160deg,
                            var(--fl-graphite-light) 0%,
                            var(--fl-graphite) 55%,
                            var(--fl-black) 100%
                        );
                "
            />
            <div
                class="absolute inset-0 opacity-[0.08]"
                style="
                    background-image: repeating-linear-gradient(
                        115deg,
                        rgba(255, 255, 255, 0.7) 0px,
                        rgba(255, 255, 255, 0.7) 1px,
                        transparent 1px,
                        transparent 48px
                    );
                "
            />
            <div
                class="legacy-line-v absolute top-1/2 left-1/2 h-2/3 -translate-x-1/2 -translate-y-1/2"
            />
        </div>
    </div>
</template>
