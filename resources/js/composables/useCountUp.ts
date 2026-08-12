import { ref, watch } from 'vue';
import type { Ref } from 'vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

/**
 * Animates a number counting up from 0 to `target` whenever `target` changes.
 * Used for dashboard/admin stats — never for decorative/fake numbers.
 */
export function useCountUp(
    target: Ref<number> | number,
    durationMs = 900,
): Ref<number> {
    const display = ref(0);
    const prefersReducedMotion = useReducedMotion();
    const targetRef = typeof target === 'number' ? ref(target) : target;

    let frame: number | null = null;

    watch(
        targetRef,
        (value) => {
            if (frame) {
                cancelAnimationFrame(frame);
            }

            if (prefersReducedMotion.value) {
                display.value = value;

                return;
            }

            const start = performance.now();
            const from = display.value;
            const delta = value - from;

            const step = (now: number) => {
                const progress = Math.min((now - start) / durationMs, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                display.value = Math.round(from + delta * eased);

                if (progress < 1) {
                    frame = requestAnimationFrame(step);
                } else {
                    display.value = value;
                }
            };

            frame = requestAnimationFrame(step);
        },
        { immediate: true },
    );

    return display;
}
