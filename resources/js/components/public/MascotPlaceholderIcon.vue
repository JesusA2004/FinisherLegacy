<script setup lang="ts">
// Abstract stand-in for the Finisher Legacy mascot: a stylised "flame of
// energy" silhouette in brand gold. Not the final character artwork — swap
// for the real asset per public/images/brand/README.md once it exists.
const props = withDefaults(
    defineProps<{
        class?: string;
    }>(),
    {
        class: 'size-16',
    },
);
</script>

<template>
    <svg
        viewBox="0 0 64 64"
        :class="props.class"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
    >
        <defs>
            <linearGradient id="mascotGold" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="var(--fl-gold-soft)" />
                <stop offset="100%" stop-color="var(--fl-gold-dim)" />
            </linearGradient>
        </defs>
        <path
            fill="url(#mascotGold)"
            d="M32 2c3 7-2 10-2 16 4-2 8-7 8-12 6 6 10 15 10 24 0 12-8 22-16 22S16 42 16 30c0-6 2-11 5-15-1 5 1 9 4 9 2 0 3-2 3-5 0-6 2-11 4-17Z"
        />
        <circle cx="26" cy="34" r="3" fill="var(--fl-black)" />
        <circle cx="38" cy="34" r="3" fill="var(--fl-black)" />
        <path
            d="M25 42c3 3 11 3 14 0"
            stroke="var(--fl-black)"
            stroke-width="2.5"
            stroke-linecap="round"
            fill="none"
        />
    </svg>
</template>
