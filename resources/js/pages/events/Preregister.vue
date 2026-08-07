<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clock } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { show as eventShow } from '@/routes/events';
import type { EventEditionDetail } from '@/types';

const { event, edition } = defineProps<{
    event: { name: string; slug: string };
    edition: EventEditionDetail | null;
}>();
</script>

<template>
    <Head :title="`Prerregistro — ${event.name}`" />

    <section class="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6 lg:px-8">
        <div
            class="mx-auto flex size-16 items-center justify-center rounded-full border border-fl-gold/30 bg-fl-graphite/60 text-fl-gold"
        >
            <Clock class="size-7" />
        </div>

        <h1 class="mt-6 text-3xl font-bold text-white">
            Prerregistro próximamente
        </h1>
        <p class="mx-auto mt-4 max-w-md text-white/60">
            Estamos preparando el prerregistro para
            <span class="text-white">{{ event.name }}</span
            ><span v-if="edition"> — {{ edition.name }}</span
            >. Muy pronto podrás reservar tu lugar aquí mismo.
        </p>

        <Button
            as-child
            variant="outline"
            class="mt-8 border-white/20 bg-transparent text-white hover:bg-white/10 hover:text-white"
        >
            <Link :href="eventShow(event.slug)">Volver al evento</Link>
        </Button>
    </section>
</template>
