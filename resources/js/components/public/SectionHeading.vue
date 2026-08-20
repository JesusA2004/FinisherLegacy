<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string;
        title: string;
        description?: string;
        align?: 'left' | 'center';
    }>(),
    {
        align: 'center',
    },
);
</script>

<template>
    <div
        class="flex flex-col gap-4"
        :class="
            align === 'center'
                ? 'items-center text-center'
                : 'items-start text-left'
        "
    >
        <span
            v-if="eyebrow"
            class="inline-flex items-center gap-2 text-xs font-semibold tracking-[0.3em] text-legacy-copper-soft uppercase"
        >
            <span class="h-px w-6 bg-legacy-copper-soft/60" aria-hidden="true" />
            {{ eyebrow }}
        </span>
        <h2
            class="max-w-3xl text-3xl font-bold tracking-tight text-legacy-bone sm:text-4xl md:text-5xl"
        >
            <template v-for="(line, index) in title.split('\n')" :key="index">
                {{ line }}<br v-if="index < title.split('\n').length - 1" />
            </template>
        </h2>
        <p
            v-if="description"
            class="max-w-2xl text-base leading-relaxed text-legacy-titanium sm:text-lg"
        >
            {{ description }}
        </p>
    </div>
</template>
