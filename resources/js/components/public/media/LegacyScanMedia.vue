<script setup lang="ts">
/**
 * Defaults to the CSS phone-scanning scene in LegacyCodePreview.vue (mosaic
 * QR + scan line) — always instant, never a network dependency. Upgrades to
 * real scan photography only once useAssetExists confirms both files are
 * actually there, so there's never a flash of a missing/broken image while
 * waiting on a 404 (that was the bug: rendering the photo optimistically
 * and falling back on @error races against network timing and sometimes
 * showed neither). See public/media/home/scan/README.md.
 */
import { useIntersectionObserver } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import LegacyCodePreview from '@/components/public/LegacyCodePreview.vue';
import { useAssetExists } from '@/composables/useAssetProbe';
import { useReducedMotion } from '@/composables/useReducedMotion';

const SCAN_SRC = '/media/home/scan/scan-phone.webp';
const RESULT_SRC = '/media/home/scan/scan-result.webp';

const { exists: scanExists } = useAssetExists(SCAN_SRC);
const { exists: resultExists } = useAssetExists(RESULT_SRC);
const readyForPhotos = computed(() => scanExists.value && resultExists.value);

const showingResult = ref(false);
const prefersReducedMotion = useReducedMotion();
const rootEl = useTemplateRef<HTMLElement>('root');
let timer: ReturnType<typeof setInterval> | undefined;

useIntersectionObserver(
    rootEl,
    ([entry]) => {
        if (
            entry?.isIntersecting &&
            !timer &&
            !prefersReducedMotion.value &&
            readyForPhotos.value
        ) {
            timer = setInterval(() => {
                showingResult.value = !showingResult.value;
            }, 2600);
        }
    },
    { threshold: 0.3 },
);
</script>

<template>
    <LegacyCodePreview v-if="!readyForPhotos" />

    <div
        v-else
        ref="root"
        class="relative mx-auto aspect-[3/4] w-full max-w-[260px] overflow-hidden rounded-[1.5rem] border border-white/10"
    >
        <img
            :src="SCAN_SRC"
            alt="Escaneando el Legacy Code de una placa"
            class="absolute inset-0 size-full object-cover transition-opacity duration-700"
            :class="showingResult ? 'opacity-0' : 'opacity-100'"
        />
        <img
            :src="RESULT_SRC"
            alt="Legacy Profile abierto después de escanear"
            class="absolute inset-0 size-full object-cover transition-opacity duration-700"
            :class="showingResult ? 'opacity-100' : 'opacity-0'"
            loading="lazy"
        />
    </div>
</template>
