<script setup lang="ts">
/**
 * Wraps a single CTA (usually a <Button as-child><Link>...) and gives it a
 * very small pointer-follow pull — a "magnetic" hint of interactivity on
 * desktop, inert on touch. Purely decorative: the wrapped element stays a
 * normal, fully clickable link/button, and the offset never exceeds a few
 * pixels so it never fights legibility or drifts the hit target noticeably.
 * Disabled entirely under prefers-reduced-motion.
 */
import { useTemplateRef } from 'vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

const prefersReducedMotion = useReducedMotion();
const el = useTemplateRef<HTMLElement>('el');

function handlePointerMove(event: PointerEvent) {
    if (prefersReducedMotion.value || !el.value) {
        return;
    }

    const rect = el.value.getBoundingClientRect();
    const relX = event.clientX - rect.left - rect.width / 2;
    const relY = event.clientY - rect.top - rect.height / 2;

    el.value.style.transform = `translate(${relX * 0.15}px, ${relY * 0.25}px)`;
}

function reset() {
    if (el.value) {
        el.value.style.transform = '';
    }
}
</script>

<template>
    <span
        ref="el"
        class="inline-block transition-transform duration-200 ease-out"
        @pointermove="handlePointerMove"
        @pointerleave="reset"
    >
        <slot />
    </span>
</template>
