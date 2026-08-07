<script setup lang="ts">
export type HowItWorksStep = {
    number: string;
    title: string;
    description: string;
};

defineProps<{
    steps: HowItWorksStep[];
}>();
</script>

<template>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div
            v-for="step in steps"
            :key="step.number"
            class="group relative flex flex-col gap-4 rounded-2xl border border-white/10 bg-fl-graphite/50 p-6 transition-colors hover:border-fl-gold/30"
        >
            <span
                class="text-4xl font-black text-white/10 transition-colors group-hover:text-fl-gold/20"
            >
                {{ step.number }}
            </span>
            <h3 class="text-lg font-semibold text-white">{{ step.title }}</h3>
            <p class="text-sm leading-relaxed text-white/60">
                {{ step.description }}
            </p>
        </div>
    </div>
</template>
