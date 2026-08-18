<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        /**
         * Placement context. Every variant currently renders the same
         * `mascot-hero` photo (it's the only pose the brand has shipped) —
         * keeping the prop lets each call site express intent, and swapping
         * in dedicated poses later only touches the `poses` map below.
         */
        variant?: 'hero' | 'small' | 'empty' | 'success';
        /** Accessible label. Pass '' to mark the image purely decorative. */
        alt?: string;
    }>(),
    {
        variant: 'hero',
        alt: 'Mascota de Finisher Legacy',
    },
);

const poses: Record<'hero' | 'small' | 'empty' | 'success', string> = {
    hero: '/images/brand/mascot/mascot-hero.jpeg',
    small: '/images/brand/mascot/mascot-hero.jpeg',
    empty: '/images/brand/mascot/mascot-hero.jpeg',
    success: '/images/brand/mascot/mascot-hero.jpeg',
};

const src = computed(() => poses[props.variant]);

const sizeClass: Record<'hero' | 'small' | 'empty' | 'success', string> = {
    hero: 'h-64 sm:h-80',
    // mascot-hero.jpeg is a narrow portrait (~0.57 ratio) — much below h-20
    // and the character's facial detail turns into an illegible blur.
    small: 'h-20',
    empty: 'h-24',
    success: 'h-28',
};

const failed = ref(false);
</script>

<template>
    <span
        v-if="!failed"
        class="fl-mascot-frame relative inline-block shrink-0 overflow-hidden rounded-[2rem]"
        :class="sizeClass[variant]"
    >
        <img
            :src="src"
            :alt="alt"
            class="h-full w-auto object-contain"
            loading="lazy"
            @error="failed = true"
        />
    </span>
</template>

<style scoped>
/*
 * mascot-hero.jpeg ships with a flat light background (no alpha channel).
 * Rather than fighting it with a blend-mode hack — which would also mute
 * the character's own gold/yellow tones — we fade its edges into the
 * surrounding dark UI with a soft radial mask, so it reads as "framed art"
 * instead of a pasted photo.
 */
.fl-mascot-frame {
    -webkit-mask-image: radial-gradient(
        closest-side,
        #000 82%,
        transparent 100%
    );
    mask-image: radial-gradient(closest-side, #000 82%, transparent 100%);
    filter: drop-shadow(
        0 8px 24px color-mix(in srgb, var(--fl-gold) 25%, transparent)
    );
}
</style>
