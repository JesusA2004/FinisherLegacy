<script setup lang="ts">
/**
 * Defaults to the CSS scene (track lanes + amber dawn light) — always
 * instant, zero network dependency. Upgrades to the real poster/video only
 * once useAssetExists confirms the file is actually there, so there's
 * never a flash of nothing while a video request is in flight or a 404
 * resolves (same fix as LegacyScanMedia/PlateMedia — optimistic-render +
 * @error-fallback raced against network timing and was flaky).
 *
 * Video is skipped below the sm breakpoint on purpose (autoplaying a hero
 * loop on mobile data is a real cost for a purely decorative background) —
 * mobile goes straight to the poster. See
 * public/media/home/hero/README.md for the asset spec — drop a file at
 * `public/media/home/hero/finisher-hero-desktop.webm` (+ `.mp4` fallback,
 * + `finisher-hero-poster.webp`) and it activates automatically, no code
 * changes.
 */
import { useMediaQuery } from '@vueuse/core';
import { computed } from 'vue';
import { useAssetExists } from '@/composables/useAssetProbe';
import { useReducedMotion } from '@/composables/useReducedMotion';

const DESKTOP_POSTER = '/media/home/hero/finisher-hero-poster.webp';
const MOBILE_POSTER = '/media/home/hero/finisher-hero-poster-mobile.webp';
const DESKTOP_VIDEO_WEBM = '/media/home/hero/finisher-hero-desktop.webm';
const DESKTOP_VIDEO_MP4 = '/media/home/hero/finisher-hero-desktop.mp4';

const prefersReducedMotion = useReducedMotion();
const isDesktop = useMediaQuery('(min-width: 640px)');

const { exists: desktopPosterExists } = useAssetExists(DESKTOP_POSTER);
const { exists: mobilePosterExists } = useAssetExists(MOBILE_POSTER);
const { exists: videoExists } = useAssetExists(DESKTOP_VIDEO_MP4);

const posterSrc = computed(() =>
    isDesktop.value
        ? desktopPosterExists.value
            ? DESKTOP_POSTER
            : null
        : mobilePosterExists.value
          ? MOBILE_POSTER
          : desktopPosterExists.value
            ? DESKTOP_POSTER
            : null,
);

const stage = computed<'video' | 'poster' | 'css'>(() => {
    if (isDesktop.value && !prefersReducedMotion.value && videoExists.value) {
        return 'video';
    }

    if (posterSrc.value) {
        return 'poster';
    }

    return 'css';
});
</script>

<template>
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <video
            v-if="stage === 'video'"
            class="absolute inset-0 size-full object-cover"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
            aria-hidden="true"
        >
            <source :src="DESKTOP_VIDEO_WEBM" type="video/webm" />
            <source :src="DESKTOP_VIDEO_MP4" type="video/mp4" />
        </video>

        <img
            v-else-if="stage === 'poster'"
            :src="posterSrc!"
            alt=""
            class="absolute inset-0 size-full object-cover"
            fetchpriority="high"
        />

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
