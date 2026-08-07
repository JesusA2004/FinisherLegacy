<script setup lang="ts">
withDefaults(
    defineProps<{
        withWordmark?: boolean;
    }>(),
    {
        withWordmark: true,
    },
);
</script>

<template>
    <span class="inline-flex items-center gap-2.5">
        <span
            class="flex size-9 shrink-0 items-center justify-center rounded-full border border-fl-gold/40 bg-gradient-to-br from-fl-graphite-light to-fl-black text-[13px] font-semibold tracking-tight text-fl-gold"
        >
            FL
        </span>
        <span v-if="withWordmark" class="flex flex-col leading-none">
            <span
                class="text-[13px] font-semibold tracking-[0.2em] text-white uppercase"
            >
                Finisher
            </span>
            <span
                class="text-[11px] font-medium tracking-[0.35em] text-fl-gold uppercase"
            >
                Legacy
            </span>
        </span>
    </span>
</template>
