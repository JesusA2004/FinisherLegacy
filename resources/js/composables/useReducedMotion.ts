import { useMediaQuery } from '@vueuse/core';

/**
 * Reactive `prefers-reduced-motion` flag. Every motion primitive in
 * `@/components/motion` reads this before animating so a single OS-level
 * setting disables all non-essential animation across the app.
 */
export function useReducedMotion() {
    return useMediaQuery('(prefers-reduced-motion: reduce)');
}
