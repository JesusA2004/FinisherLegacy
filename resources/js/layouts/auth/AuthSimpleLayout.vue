<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Award, IdCard, Medal, QrCode } from '@lucide/vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import FinisherMascot from '@/components/public/FinisherMascot.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const chain = [
    { icon: Medal, label: 'Medalla física' },
    { icon: Award, label: 'Placa' },
    { icon: QrCode, label: 'Legacy Code' },
    { icon: IdCard, label: 'Legacy Profile' },
];
</script>

<template>
    <div class="dark grid min-h-svh bg-fl-black lg:grid-cols-2">
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-fl-graphite-light via-fl-graphite to-fl-black p-12 lg:flex"
        >
            <div
                class="absolute -top-24 -left-24 size-72 animate-pulse rounded-full bg-fl-gold/10 blur-3xl"
                style="animation-duration: 6s"
            />
            <div
                class="absolute -right-16 bottom-0 size-64 rounded-full bg-fl-gold/5 blur-3xl"
            />

            <Link :href="home()" class="relative z-10">
                <FinisherLegacyLogo variant="horizontal" size="xl" />
            </Link>

            <div class="relative z-10 max-w-sm">
                <p class="text-2xl leading-snug font-bold text-white">
                    Tu meta termina.
                    <span class="text-fl-gold">Tu historia no.</span>
                </p>
                <p class="mt-4 text-sm leading-relaxed text-white/50">
                    Cada Legacy ID conecta tus logros deportivos con una
                    historia que puedes conservar, revivir y compartir.
                </p>
            </div>

            <div class="relative z-10 flex items-end justify-between gap-6">
                <div class="flex flex-col gap-3">
                    <template v-for="(step, index) in chain" :key="step.label">
                        <div class="flex items-center gap-3">
                            <div
                                class="fl-hover-glow flex size-9 shrink-0 items-center justify-center rounded-full border border-fl-gold/30 bg-fl-black text-fl-gold transition-colors duration-300"
                            >
                                <component :is="step.icon" class="size-4" />
                            </div>
                            <span class="text-sm text-white/70">{{
                                step.label
                            }}</span>
                        </div>
                        <div
                            v-if="index < chain.length - 1"
                            class="ml-[18px] h-4 w-px bg-fl-gold/20"
                        />
                    </template>
                </div>

                <FinisherMascot
                    variant="small"
                    alt=""
                    class="hidden shrink-0 opacity-70 xl:block"
                />
            </div>
        </div>

        <div
            class="flex flex-col items-center justify-center px-6 py-16 sm:px-10"
        >
            <div class="w-full max-w-sm">
                <Link :href="home()" class="mb-8 flex justify-center lg:hidden">
                    <FinisherLegacyLogo variant="mark" size="lg" />
                </Link>

                <div
                    v-if="title || description"
                    class="mb-8 space-y-2 text-center lg:text-left"
                >
                    <h1 v-if="title" class="text-2xl font-bold text-white">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-white/50">
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
