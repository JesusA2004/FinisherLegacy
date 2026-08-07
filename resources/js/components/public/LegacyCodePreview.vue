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
    { icon: Award, label: 'Placa Finisher Legacy' },
    { icon: QrCode, label: 'Legacy Code' },
    { icon: UserRound, label: 'Legacy Profile' },
];
</script>

<template>
    <div
        class="flex flex-col items-center gap-6 rounded-2xl border border-white/10 bg-fl-graphite/50 p-8 sm:flex-row sm:justify-center sm:gap-4"
    >
        <template v-for="(step, index) in steps" :key="step.label">
            <div class="flex flex-col items-center gap-3 text-center">
                <div
                    class="flex size-16 items-center justify-center rounded-full border border-fl-gold/30 bg-fl-black text-fl-gold shadow-[0_0_30px_-8px_rgba(212,175,106,0.5)]"
                >
                    <component :is="step.icon" class="size-7" />
                </div>
                <span class="text-sm font-medium text-white/80">{{
                    step.label
                }}</span>
                <span
                    v-if="index === 1"
                    class="rounded-md border border-white/10 bg-fl-black px-2 py-1 font-mono text-xs text-fl-gold"
                >
                    {{ sampleCode }}
                </span>
            </div>

            <ChevronRight
                v-if="index < steps.length - 1"
                class="size-6 rotate-90 text-white/20 sm:rotate-0"
            />
        </template>
    </div>
</template>
