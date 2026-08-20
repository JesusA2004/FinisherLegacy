<script setup lang="ts">
/**
 * Defaults to PlateShowcase.vue's CSS-drawn plate (tilt + glare + demo
 * data) — always instant. Upgrades to real front/back photography only once
 * useAssetExists confirms the front photo is there, avoiding the same
 * flash-of-nothing bug LegacyScanMedia had. See
 * public/media/home/plate/README.md.
 */
import { ref } from 'vue';
import PlateShowcase from '@/components/public/PlateShowcase.vue';
import { useAssetExists } from '@/composables/useAssetProbe';

const FRONT_SRC = '/media/home/plate/legacy-plate-front.webp';
const BACK_SRC = '/media/home/plate/legacy-plate-back.webp';

const { exists: frontExists } = useAssetExists(FRONT_SRC);
const showingBack = ref(false);
</script>

<template>
    <PlateShowcase v-if="!frontExists" />

    <div v-else class="mx-auto w-full max-w-sm" style="perspective: 1200px">
        <button
            type="button"
            class="fl-focus-glow relative aspect-[3/2] w-full overflow-hidden rounded-xl border border-white/15 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.7)] transition-transform duration-500"
            :style="{
                transformStyle: 'preserve-3d',
                transform: showingBack ? 'rotateY(180deg)' : 'rotateY(0deg)',
            }"
            :aria-label="
                showingBack
                    ? 'Ver anverso de la placa'
                    : 'Ver reverso de la placa'
            "
            @click="showingBack = !showingBack"
        >
            <img
                :src="FRONT_SRC"
                alt="Placa Finisher Legacy — anverso"
                class="absolute inset-0 size-full object-cover"
                style="backface-visibility: hidden"
            />
            <img
                :src="BACK_SRC"
                alt="Placa Finisher Legacy — reverso"
                class="absolute inset-0 size-full object-cover"
                style="backface-visibility: hidden; transform: rotateY(180deg)"
                loading="lazy"
            />
        </button>

        <p
            class="mt-3 text-center text-xs tracking-wide text-white/40 uppercase"
        >
            Toca la placa para ver el reverso
        </p>
    </div>
</template>
