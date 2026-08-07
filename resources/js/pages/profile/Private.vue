<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';

defineProps<{
    username: string;
}>();
</script>

<template>
    <Head :title="`@${username}`" />

    <section class="mx-auto max-w-md px-4 py-32 text-center sm:px-6">
        <div
            class="mx-auto flex size-16 items-center justify-center rounded-full border border-white/15 bg-fl-graphite text-white/50"
        >
            <Lock class="size-7" />
        </div>
        <h1 class="mt-6 text-2xl font-bold text-white">
            Este Legacy Profile es privado.
        </h1>
        <p class="mt-3 text-sm text-white/50">
            @{{ username }} decidió mantener su colección solo para sí mismo.
        </p>
    </section>
</template>
