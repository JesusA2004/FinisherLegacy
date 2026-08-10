<script setup lang="ts">
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <div class="dark min-h-svh bg-fl-black">
        <slot />
        <Toaster />
    </div>
</template>
