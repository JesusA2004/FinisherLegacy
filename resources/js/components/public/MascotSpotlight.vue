<script setup lang="ts">
import MascotPlaceholderIcon from '@/components/public/MascotPlaceholderIcon.vue';

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

        <div class="relative">
            <div
                class="flex size-24 items-center justify-center rounded-full border border-fl-gold/30 bg-fl-black"
            >
                <MascotPlaceholderIcon class="size-14" />
            </div>
            <span
                class="absolute -bottom-2 left-1/2 -translate-x-1/2 rounded-full border border-white/10 bg-fl-black px-2 py-0.5 text-[9px] font-medium tracking-wide whitespace-nowrap text-white/40 uppercase"
            >
                Mascota — próximamente
            </span>
        </div>

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
