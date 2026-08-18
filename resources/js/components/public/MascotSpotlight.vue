<script setup lang="ts">
import FinisherMascot from '@/components/public/FinisherMascot.vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
    }>(),
    {},
);
</script>

<template>
    <div
        class="relative flex flex-col items-center gap-6 overflow-hidden rounded-2xl border border-fl-gold/20 bg-gradient-to-b from-fl-graphite/60 to-fl-black p-10 text-center"
    >
        <div
            class="absolute -top-16 left-1/2 size-48 -translate-x-1/2 rounded-full bg-fl-gold/10 blur-3xl"
        />

        <FinisherMascot variant="hero" class="relative" />

        <div class="relative mt-2 max-w-md">
            <h3 class="text-xl font-bold text-white">{{ title }}</h3>
            <p v-if="description" class="mt-2 text-sm text-white/60">
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.default" class="relative">
            <slot />
        </div>
    </div>
</template>
