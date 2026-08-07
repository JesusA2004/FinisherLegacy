<script setup lang="ts">
import MascotPlaceholderIcon from '@/components/public/MascotPlaceholderIcon.vue';

defineProps<{
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-white/15 bg-fl-graphite/30 px-6 py-14 text-center"
    >
        <div
            class="flex size-16 items-center justify-center rounded-full border border-fl-gold/25 bg-fl-black"
        >
            <MascotPlaceholderIcon class="size-9" />
        </div>

        <div class="max-w-sm">
            <h3 class="text-base font-semibold text-white">{{ title }}</h3>
            <p v-if="description" class="mt-1.5 text-sm text-white/50">
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.default">
            <slot />
        </div>
    </div>
</template>
