<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        /** `mark` = only the FL glyph. `horizontal` = FL glyph + "Tu historia. Tu legado." lockup. */
        variant?: 'mark' | 'horizontal';
        /** Color treatment of the asset itself — pick the one that reads on the surface behind it. */
        tone?: 'light' | 'gold' | 'dark' | 'auto';
        size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        variant: 'horizontal',
        tone: 'auto',
        size: 'md',
    },
);

// The product surface is dark-first end to end (navbar, sidebar, auth,
// footer all render on fl-black), so "auto" resolves to the light-on-dark
// treatment. Callers on a light surface should pass tone="dark" explicitly.
const resolvedTone = computed(() =>
    props.tone === 'auto' ? 'light' : props.tone,
);

const sources: Record<
    'mark' | 'horizontal',
    Record<'light' | 'gold' | 'dark', string>
> = {
    mark: {
        light: '/images/brand/logo/logo-mark-white.png',
        gold: '/images/brand/logo/logo-mark-gold.png',
        dark: '/images/brand/logo/logo-mark-black.png',
    },
    horizontal: {
        light: '/images/brand/logo/logo-horizontal-light.png',
        gold: '/images/brand/logo/logo-horizontal-gold.png',
        dark: '/images/brand/logo/logo-horizontal-dark.png',
    },
};

const src = computed(() => sources[props.variant][resolvedTone.value]);

const heights: Record<'xs' | 'sm' | 'md' | 'lg' | 'xl', string> = {
    xs: 'h-5',
    sm: 'h-7',
    md: 'h-9',
    lg: 'h-12',
    xl: 'h-16',
};

const failed = ref(false);
</script>

<template>
    <span
        v-if="failed"
        class="inline-flex items-center font-black tracking-tighter text-fl-gold uppercase"
        :class="heights[size]"
    >
        FL
    </span>
    <img
        v-else
        :src="src"
        alt="Finisher Legacy"
        class="w-auto object-contain"
        :class="heights[size]"
        @error="failed = true"
    />
</template>
