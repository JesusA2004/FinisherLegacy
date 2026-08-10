<script setup lang="ts">
/**
 * Wrap a grid/list of cards with this to reveal them with a staggered
 * fade+translate as the group enters the viewport, instead of adding
 * per-card Reveal delays by hand.
 */
import { useIntersectionObserver } from '@vueuse/core';
import { useTemplateRef } from 'vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

const props = withDefaults(
    defineProps<{
        as?: string;
        itemSelector?: string;
        staggerMs?: number;
        durationMs?: number;
    }>(),
    {
        as: 'div',
        itemSelector: ':scope > *',
        staggerMs: 60,
        durationMs: 450,
    },
);

const prefersReducedMotion = useReducedMotion();
const el = useTemplateRef<HTMLElement>('el');

useIntersectionObserver(
    el,
    ([entry]) => {
        if (!entry?.isIntersecting || !el.value || prefersReducedMotion.value) {
return;
}

        const items = el.value.querySelectorAll<HTMLElement>(
            props.itemSelector,
        );

        items.forEach((item, index) => {
            item.style.transitionDelay = `${index * props.staggerMs}ms`;
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        });
    },
    { threshold: 0.1 },
);
</script>

<template>
    <component :is="as" ref="el" class="[&>*]:fl-stagger-item">
        <slot />
    </component>
</template>

<style>
.fl-stagger-item {
    opacity: 0;
    transform: translateY(16px);
    transition-property: opacity, transform;
    transition-duration: v-bind('`${durationMs}ms`');
    transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
}

@media (prefers-reduced-motion: reduce) {
    .fl-stagger-item {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
</style>
