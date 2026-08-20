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
    <section class="relative overflow-hidden border-t border-legacy-titanium/10">
        <div
            class="absolute inset-0 bg-gradient-to-br from-legacy-carbon via-legacy-ink to-legacy-ink"
        />
        <div
            class="absolute -top-32 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-legacy-copper/10 blur-3xl"
        />
        <div class="legacy-line absolute inset-x-0 top-0" />

        <div
            class="relative mx-auto flex max-w-4xl flex-col items-center gap-6 px-4 py-24 text-center sm:px-6 lg:px-8"
        >
            <h2
                class="text-3xl font-bold tracking-tight text-legacy-bone sm:text-4xl"
            >
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="max-w-xl text-base text-legacy-titanium sm:text-lg"
            >
                {{ description }}
            </p>
            <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                <Button
                    as-child
                    size="lg"
                    class="bg-legacy-copper px-8 text-legacy-bone hover:bg-legacy-copper-soft"
                >
                    <Link :href="primaryHref">{{ primaryLabel }}</Link>
                </Button>
                <Button
                    v-if="secondaryLabel && secondaryHref"
                    as-child
                    size="lg"
                    variant="outline"
                    class="border-legacy-titanium/25 bg-transparent px-8 text-legacy-bone hover:bg-white/10 hover:text-legacy-bone"
                >
                    <Link :href="secondaryHref">{{ secondaryLabel }}</Link>
                </Button>
            </div>
        </div>
    </section>
</template>
