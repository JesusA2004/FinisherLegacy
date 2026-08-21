<script setup lang="ts">
/**
 * `finisher-hero-desktop.mp4` is a real, confirmed-present asset (see
 * public/media/home/hero/README.md) — it's rendered directly, no
 * useAssetExists probe needed for it (brand system §20: don't HEAD-check a
 * static path we already know exists). There's no `.webm` transcode and no
 * poster image yet, so those aren't referenced — a `<source>` pointing at a
 * file that doesn't exist would just be a guaranteed 404 on every load.
 * If a webm/poster set gets added later, reintroduce the cascade here.
 *
 * Plays on every viewport width (parity request, 2026-08-21 polish pass —
 * mobile used to get the CSS scene only). `preload="metadata"` keeps the
 * initial fetch light, and the video pauses itself once the Hero scrolls
 * out of view (brand system §P6: never keep playing what isn't visible) —
 * it's `muted`+`loop` so resuming on re-entry is seamless. Falls back to
 * the CSS scene under prefers-reduced-motion or if the file fails to load.
 */
import { useIntersectionObserver } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

const DESKTOP_VIDEO_MP4 = '/media/home/hero/finisher-hero-desktop.mp4';

const prefersReducedMotion = useReducedMotion();
const videoFailed = ref(false);
const rootEl = useTemplateRef<HTMLElement>('root');
const videoEl = useTemplateRef<HTMLVideoElement>('video');

const stage = computed<'video' | 'css'>(() =>
    !prefersReducedMotion.value && !videoFailed.value ? 'video' : 'css',
);

useIntersectionObserver(
    rootEl,
    ([entry]) => {
        if (!videoEl.value) {
            return;
        }

        if (entry?.isIntersecting) {
            videoEl.value.play().catch(() => {});
        } else {
            videoEl.value.pause();
        }
    },
    { threshold: 0 },
);
</script>

<template>
    <div
        ref="root"
        class="pointer-events-none absolute inset-0 overflow-hidden"
    >
        <video
            v-if="stage === 'video'"
            ref="video"
            class="absolute inset-0 size-full object-cover"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
            aria-hidden="true"
            @error="videoFailed = true"
        >
            <source :src="DESKTOP_VIDEO_MP4" type="video/mp4" />
        </video>

        <!-- CSS fallback scene: track lanes + amber dawn light -->
        <div v-else class="absolute inset-0">
            <div
                class="absolute inset-0"
                style="
                    background:
                        radial-gradient(
                            ellipse 70% 55% at 18% 15%,
                            color-mix(in srgb, var(--fl-gold) 22%, transparent),
                            transparent 60%
                        ),
                        radial-gradient(
                            ellipse 60% 50% at 85% 85%,
                            color-mix(
                                in srgb,
                                var(--fl-gold-soft) 10%,
                                transparent
                            ),
                            transparent 60%
                        ),
                        linear-gradient(
                            180deg,
                            var(--fl-black) 0%,
                            var(--fl-graphite) 55%,
                            var(--fl-black) 100%
                        );
                "
            />
            <div
                class="absolute inset-0 opacity-[0.07]"
                style="
                    background-image: repeating-linear-gradient(
                        115deg,
                        rgba(255, 255, 255, 0.6) 0px,
                        rgba(255, 255, 255, 0.6) 1px,
                        transparent 1px,
                        transparent 64px
                    );
                "
            />
        </div>

        <!-- Legibility overlay — only needed over real photo/video, the CSS
             scene is already tuned dark enough on its own. -->
        <div v-if="stage !== 'css'" class="absolute inset-0 bg-fl-black/55" />
        <div class="absolute inset-x-0 bottom-0 h-px bg-white/10" />
    </div>
</template>
