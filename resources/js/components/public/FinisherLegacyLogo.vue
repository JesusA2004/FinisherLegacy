<script setup lang="ts">
withDefaults(
    defineProps<{
        withWordmark?: boolean;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        withWordmark: true,
        size: 'md',
    },
);

const sizes = {
    sm: { box: 'size-8', text: 'text-[11px]' },
    md: { box: 'size-9', text: 'text-[13px]' },
    lg: { box: 'size-12', text: 'text-base' },
};
</script>

<template>
    <span class="inline-flex items-center gap-2.5">
        <span
            class="relative flex shrink-0 items-center justify-center"
            :class="sizes[size].box"
        >
            <!-- Geometric, slightly inclined monogram badge -->
            <span
                class="absolute inset-0 -rotate-6 bg-gradient-to-br from-fl-gold via-fl-gold-soft to-fl-gold-dim [clip-path:polygon(18%_0,100%_0,82%_100%,0_100%)]"
            />
            <span
                class="absolute inset-[1.5px] -rotate-6 bg-fl-black [clip-path:polygon(18%_0,100%_0,82%_100%,0_100%)]"
            />
            <span
                class="relative font-black tracking-tighter text-fl-gold"
                :class="sizes[size].text"
            >
                FL
            </span>
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
