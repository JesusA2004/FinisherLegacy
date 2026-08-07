<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Award, IdCard, Medal, QrCode } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    title: string;
    subtitle: string;
    primaryLabel: string;
    primaryHref: NonNullable<InertiaLinkProps['href']>;
    secondaryLabel: string;
    secondaryHref: NonNullable<InertiaLinkProps['href']>;
}>();

const chain = [
    { icon: Medal, label: 'Medalla física' },
    { icon: Award, label: 'Placa' },
    { icon: QrCode, label: 'Legacy Code' },
    { icon: IdCard, label: 'Legacy Profile' },
];
</script>

<template>
    <section class="relative overflow-hidden">
        <div
            class="absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,rgba(212,175,106,0.18),transparent)]"
        />
        <div
            class="absolute top-1/3 -left-24 size-72 rounded-full bg-fl-gold/10 blur-3xl"
        />
        <div
            class="absolute -right-24 bottom-0 size-72 rounded-full bg-fl-gold/5 blur-3xl"
        />

        <div
            class="relative mx-auto grid max-w-7xl gap-16 px-4 py-24 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-32"
        >
            <div class="flex flex-col items-start gap-8">
                <h1
                    class="text-4xl leading-[1.05] font-black tracking-tight text-white sm:text-5xl lg:text-6xl"
                >
                    <template
                        v-for="(line, index) in title.split('\n')"
                        :key="index"
                    >
                        <span :class="{ 'text-fl-gold': index === 1 }">{{
                            line
                        }}</span>
                        <br v-if="index < title.split('\n').length - 1" />
                    </template>
                </h1>

                <p class="max-w-lg text-lg leading-relaxed text-white/60">
                    {{ subtitle }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button
                        as-child
                        size="lg"
                        class="bg-fl-gold px-8 text-base text-fl-black hover:bg-fl-gold-soft"
                    >
                        <Link :href="primaryHref">{{ primaryLabel }}</Link>
                    </Button>
                    <Button
                        as-child
                        size="lg"
                        variant="outline"
                        class="border-white/20 bg-transparent px-8 text-base text-white hover:bg-white/10 hover:text-white"
                    >
                        <Link :href="secondaryHref">{{ secondaryLabel }}</Link>
                    </Button>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-sm lg:mx-0 lg:ml-auto">
                <div
                    class="absolute -inset-6 rounded-[2rem] bg-gradient-to-br from-fl-gold/15 via-transparent to-transparent blur-2xl"
                />
                <div
                    class="relative flex flex-col gap-5 rounded-[1.75rem] border border-white/10 bg-fl-graphite/60 p-8 backdrop-blur-sm"
                >
                    <template v-for="(step, index) in chain" :key="step.label">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex size-12 shrink-0 items-center justify-center rounded-full border border-fl-gold/30 bg-fl-black text-fl-gold"
                            >
                                <component :is="step.icon" class="size-5" />
                            </div>
                            <span class="text-sm font-medium text-white/80">{{
                                step.label
                            }}</span>
                        </div>
                        <div
                            v-if="index < chain.length - 1"
                            class="ml-6 h-6 w-px bg-gradient-to-b from-fl-gold/40 to-transparent"
                        />
                    </template>
                </div>
            </div>
        </div>
    </section>
</template>
