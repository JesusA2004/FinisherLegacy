<script setup lang="ts">
import { Award, ChevronRight, QrCode, UserRound } from '@lucide/vue';

withDefaults(
    defineProps<{
        sampleCode?: string;
    }>(),
    {
        sampleCode: 'FL-8K3XP7M',
    },
);

const steps = [
    { icon: Award, label: 'Placa Finisher Legacy', accent: 'copper' as const },
    { icon: QrCode, label: 'Legacy Code', accent: 'ice' as const },
    { icon: UserRound, label: 'Legacy Profile', accent: 'ice' as const },
];
</script>

<template>
    <div
        class="flex flex-col items-center gap-6 rounded-2xl border border-legacy-titanium/10 bg-legacy-carbon/50 p-8 sm:flex-row sm:justify-center sm:gap-4"
    >
        <template v-for="(step, index) in steps" :key="step.label">
            <div class="flex flex-col items-center gap-3 text-center">
                <div
                    class="relative flex size-16 items-center justify-center overflow-hidden rounded-full border"
                    :class="
                        step.accent === 'ice'
                            ? 'border-legacy-ice/30 text-legacy-ice shadow-[0_0_30px_-8px_rgba(115,199,232,0.5)]'
                            : 'border-legacy-copper/30 text-legacy-copper-soft shadow-[0_0_30px_-8px_rgba(184,111,68,0.5)]'
                    "
                >
                    <component :is="step.icon" class="size-7" />
                    <span
                        v-if="index === 1"
                        class="absolute inset-x-1 h-px animate-[fl-scan_2.4s_ease-in-out_infinite] bg-legacy-ice/70 motion-reduce:hidden"
                        aria-hidden="true"
                    />
                </div>
                <span class="text-sm font-medium text-legacy-bone/85">{{
                    step.label
                }}</span>
                <span
                    v-if="index === 1"
                    class="legacy-numeric rounded-md border border-legacy-titanium/15 bg-legacy-ink px-2 py-1 font-mono text-xs text-legacy-ice"
                >
                    {{ sampleCode }}
                </span>
            </div>

            <ChevronRight
                v-if="index < steps.length - 1"
                class="size-6 rotate-90 text-legacy-titanium/25 sm:rotate-0"
            />
        </template>
    </div>
</template>

<style scoped>
@keyframes fl-scan {
    0%,
    100% {
        top: 20%;
    }
    50% {
        top: 75%;
    }
}
</style>
