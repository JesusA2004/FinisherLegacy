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
            class="group relative flex flex-col gap-4 rounded-2xl border border-legacy-titanium/10 bg-legacy-carbon/50 p-6 transition-colors hover:border-legacy-copper/30"
        >
            <span
                class="legacy-numeric text-4xl font-black text-legacy-titanium/15 transition-colors group-hover:text-legacy-copper/25"
            >
                {{ step.number }}
            </span>
            <h3 class="text-lg font-semibold text-legacy-bone">{{ step.title }}</h3>
            <p class="text-sm leading-relaxed text-legacy-titanium">
                {{ step.description }}
            </p>
        </div>
    </div>
</template>
