<script setup lang="ts">
import PublicFooter from '@/components/public/PublicFooter.vue';
import PublicNavbar from '@/components/public/PublicNavbar.vue';
</script>

<template>
    <div class="min-h-screen bg-fl-black text-white antialiased">
        <PublicNavbar />
        <main class="pt-16">
            <slot />
        </main>
        <PublicFooter />
    </div>
</template>
