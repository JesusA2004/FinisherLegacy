<script setup lang="ts">
/**
 * Thin fixed bar tracking how far down the page the visitor has scrolled —
 * the Legacy Line doubling as a page-level progress cue (brand system §23.3
 * / §23.4). Purely visual feedback, not a navigation control, so it stays
 * on even under prefers-reduced-motion (only the width-fill transition is
 * disabled there, not the bar itself).
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';

const progress = ref(0);

function handleScroll() {
    const scrollable =
        document.documentElement.scrollHeight - window.innerHeight;

    progress.value = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
}

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
    window.addEventListener('resize', handleScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
    window.removeEventListener('resize', handleScroll);
});
</script>

<template>
    <div
        class="fixed inset-x-0 top-0 z-[60] h-[2px] bg-transparent"
        aria-hidden="true"
    >
        <div
            class="fl-scroll-progress-fill h-full bg-gradient-to-r from-fl-gold-dim via-fl-gold to-fl-gold-soft"
            :style="{ width: `${progress}%` }"
        />
    </div>
</template>

<style scoped>
.fl-scroll-progress-fill {
    transition: width 120ms linear;
}

@media (prefers-reduced-motion: reduce) {
    .fl-scroll-progress-fill {
        transition: none;
    }
}
</style>
