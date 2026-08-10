<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        :class="className"
        v-bind="$attrs"
    >
        <g transform="translate(32,32) skewX(-9) translate(-32,-32)">
            <rect x="11" y="15" width="7.5" height="34" rx="1.5" fill="currentColor" />
            <rect x="11" y="15" width="21" height="7.5" rx="1.5" fill="currentColor" />
            <rect x="11" y="30" width="16" height="7.5" rx="1.5" fill="currentColor" />
            <rect x="37" y="15" width="7.5" height="34" rx="1.5" fill="currentColor" />
            <rect x="37" y="41.5" width="18.5" height="7.5" rx="1.5" fill="currentColor" />
        </g>
    </svg>
</template>
