<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

defineProps<{
    title: string;
    description?: string;
    primaryLabel: string;
    primaryHref: NonNullable<InertiaLinkProps['href']>;
    secondaryLabel?: string;
    secondaryHref?: NonNullable<InertiaLinkProps['href']>;
}>();
</script>

<template>
    <section class="relative overflow-hidden border-t border-white/10">
        <div
            class="absolute inset-0 bg-gradient-to-br from-fl-graphite via-fl-black to-fl-black"
        />
        <div
            class="absolute -top-32 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-fl-gold/10 blur-3xl"
        />

        <div
            class="relative mx-auto flex max-w-4xl flex-col items-center gap-6 px-4 py-20 text-center sm:px-6 lg:px-8"
        >
            <h2
                class="text-3xl font-bold tracking-tight text-white sm:text-4xl"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="max-w-xl text-base text-white/60 sm:text-lg"
            >
                {{ description }}
            </p>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                <Button
                    as-child
                    size="lg"
                    class="bg-fl-gold px-8 text-fl-black hover:bg-fl-gold-soft"
                >
                    <Link :href="primaryHref">{{ primaryLabel }}</Link>
                </Button>
                <Button
                    v-if="secondaryLabel && secondaryHref"
                    as-child
                    size="lg"
                    variant="outline"
                    class="border-white/20 bg-transparent px-8 text-white hover:bg-white/10 hover:text-white"
                >
                    <Link :href="secondaryHref">{{ secondaryLabel }}</Link>
                </Button>
            </div>
        </div>
    </section>
</template>
