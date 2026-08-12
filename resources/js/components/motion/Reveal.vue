<script setup lang="ts">
/**
 * Fades + translates its content in once it enters the viewport.
 * Wrap sections/cards with this instead of hand-rolling IntersectionObserver
 * logic per component. Respects prefers-reduced-motion (shows content
 * instantly, no motion).
 */
import { useIntersectionObserver } from '@vueuse/core';
import { useTemplateRef } from 'vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

const props = withDefaults(
    defineProps<{
        as?: string;
        direction?: 'up' | 'down' | 'left' | 'right' | 'none';
        delayMs?: number;
        durationMs?: number;
        once?: boolean;
    }>(),
    {
        as: 'div',
        direction: 'up',
        delayMs: 0,
        durationMs: 500,
        once: true,
    },
);

const prefersReducedMotion = useReducedMotion();
const el = useTemplateRef<HTMLElement>('el');

const offsets: Record<string, string> = {
    up: 'translateY(16px)',
    down: 'translateY(-16px)',
    left: 'translateX(16px)',
    right: 'translateX(-16px)',
    none: 'translateY(0)',
};

useIntersectionObserver(
    el,
    ([entry]) => {
        if (!entry) {
            return;
        }

        const node = el.value;

        if (!node) {
            return;
        }

        if (entry.isIntersecting) {
            node.style.opacity = '1';
            node.style.transform = 'translate(0, 0)';
        } else if (!props.once) {
            node.style.opacity = '0';
            node.style.transform = offsets[props.direction];
        }
    },
    { threshold: 0.15 },
);
</script>

<template>
    <component
        :is="as"
        ref="el"
        :style="
            prefersReducedMotion
                ? undefined
                : {
                      opacity: 0,
                      transform: offsets[direction],
                      transitionProperty: 'opacity, transform',
                      transitionDuration: `${durationMs}ms`,
                      transitionTimingFunction: 'cubic-bezier(0.16, 1, 0.3, 1)',
                      transitionDelay: `${delayMs}ms`,
                  }
        "
    >
        <slot />
    </component>
</template>
