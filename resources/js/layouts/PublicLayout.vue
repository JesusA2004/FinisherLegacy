<script setup lang="ts">
import ScrollProgress from '@/components/motion/ScrollProgress.vue';
import BackToTop from '@/components/public/BackToTop.vue';
import PublicFooter from '@/components/public/PublicFooter.vue';
import PublicNavbar from '@/components/public/PublicNavbar.vue';
</script>

<template>
    <div class="min-h-screen bg-fl-black text-white antialiased">
        <ScrollProgress />
        <PublicNavbar />
        <main class="pt-16">
            <slot />
        </main>
        <PublicFooter />
        <BackToTop />
    </div>
</template>
