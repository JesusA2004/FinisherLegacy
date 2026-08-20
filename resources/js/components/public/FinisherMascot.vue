<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        /**
         * Placement context. Every variant currently renders the same
         * `mascot-hero` pose (it's the only one the brand has shipped) —
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

// mascot-hero.png is a real transparent PNG (RGBA, alpha-cut around the
// character) — unlike the older mascot-hero.jpeg, which shipped with an
// opaque light background and needed a radial mask hack to fade into dark
// UI. The PNG needs none of that: it renders straight, no mask, no frame,
// no card (brand system §23).
const poses: Record<'hero' | 'small' | 'empty' | 'success', string> = {
    hero: '/images/brand/mascot/mascot-hero.png',
    small: '/images/brand/mascot/mascot-hero.png',
    empty: '/images/brand/mascot/mascot-hero.png',
    success: '/images/brand/mascot/mascot-hero.png',
};

const src = computed(() => poses[props.variant]);

const sizeClass: Record<'hero' | 'small' | 'empty' | 'success', string> = {
    hero: 'h-64 sm:h-80',
    small: 'h-20',
    empty: 'h-24',
    success: 'h-28',
};

const failed = ref(false);
</script>

<template>
    <span
        v-if="!failed"
        class="fl-mascot-glow relative inline-block shrink-0"
        :class="sizeClass[variant]"
    >
        <img
            :src="src"
            :alt="alt"
            class="h-full w-auto object-contain"
            loading="lazy"
            decoding="async"
            @error="failed = true"
        />
    </span>
</template>

<style scoped>
/* A soft ground-glow instead of a frame — enough to seat the character on
   dark UI without boxing it in an oval, card, or mask. */
.fl-mascot-glow {
    filter: drop-shadow(
        0 12px 28px color-mix(in srgb, var(--fl-gold) 30%, transparent)
    );
}
</style>
