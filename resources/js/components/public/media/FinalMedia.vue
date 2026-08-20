<script setup lang="ts">
/**
 * Optional cinematic background for the closing CTA — checked with
 * useAssetExists before ever rendering a <video>/<img> src, so there's
 * never a flash of a missing asset (see HomeHeroMedia.vue for why the
 * render-then-@error pattern was flaky). Renders nothing when absent —
 * CTASection already carries its own gradient/Legacy Line as the base
 * layer. See public/media/home/final/README.md.
 */
import { computed } from 'vue';
import { useAssetExists } from '@/composables/useAssetProbe';
import { useReducedMotion } from '@/composables/useReducedMotion';

const VIDEO_WEBM = '/media/home/final/legacy-final.webm';
const VIDEO_MP4 = '/media/home/final/legacy-final.mp4';
const POSTER = '/media/home/final/legacy-final-poster.webp';

const prefersReducedMotion = useReducedMotion();
const { exists: videoExists } = useAssetExists(VIDEO_MP4);
const { exists: posterExists } = useAssetExists(POSTER);

const stage = computed<'video' | 'poster' | 'none'>(() => {
    if (!prefersReducedMotion.value && videoExists.value) {
        return 'video';
    }

    if (posterExists.value) {
        return 'poster';
    }

    return 'none';
});
</script>

<template>
    <div v-if="stage !== 'none'" class="absolute inset-0 overflow-hidden">
        <video
            v-if="stage === 'video'"
            class="absolute inset-0 size-full object-cover opacity-40"
            autoplay
            muted
            loop
            playsinline
            preload="metadata"
            aria-hidden="true"
        >
            <source :src="VIDEO_WEBM" type="video/webm" />
            <source :src="VIDEO_MP4" type="video/mp4" />
        </video>
        <img
            v-else
            :src="POSTER"
            alt=""
            class="absolute inset-0 size-full object-cover opacity-40"
        />
        <div class="absolute inset-0 bg-fl-black/50" />
    </div>
</template>
