<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Award, IdCard, Medal, QrCode } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import MagneticButton from '@/components/public/MagneticButton.vue';
import HomeHeroMedia from '@/components/public/media/HomeHeroMedia.vue';
import ScrollCueButton from '@/components/public/ScrollCueButton.vue';
import { Button } from '@/components/ui/button';
import { useReducedMotion } from '@/composables/useReducedMotion';

defineProps<{
    title: string;
    subtitle: string;
    primaryLabel: string;
    primaryHref: NonNullable<InertiaLinkProps['href']>;
    secondaryLabel: string;
    secondaryHref: NonNullable<InertiaLinkProps['href']>;
}>();

const chain = [
    { icon: Medal, label: 'Medalla física', accent: 'gold' as const },
    { icon: Award, label: 'Placa Legacy', accent: 'gold' as const },
    { icon: QrCode, label: 'Legacy Code', accent: 'gold-soft' as const },
    { icon: IdCard, label: 'Legacy Profile', accent: 'gold-soft' as const },
];

// Very subtle cursor-driven depth on the plate/code/profile stack — a hint
// of dimensionality, never enough to fight legibility. Disabled entirely
// under prefers-reduced-motion (brand system §41 + §46).
const prefersReducedMotion = useReducedMotion();
const stackEl = useTemplateRef<HTMLElement>('stack');

function handlePointerMove(event: PointerEvent) {
    if (prefersReducedMotion.value || !stackEl.value) {
        return;
    }

    const rect = stackEl.value.getBoundingClientRect();
    const relX = (event.clientX - rect.left) / rect.width - 0.5;
    const relY = (event.clientY - rect.top) / rect.height - 0.5;

    stackEl.value.style.setProperty('--tilt-x', `${relY * -4}deg`);
    stackEl.value.style.setProperty('--tilt-y', `${relX * 4}deg`);
}

function resetTilt() {
    stackEl.value?.style.setProperty('--tilt-x', '0deg');
    stackEl.value?.style.setProperty('--tilt-y', '0deg');
}
</script>

<template>
    <section
        class="relative flex min-h-[100svh] items-center overflow-hidden bg-fl-black"
    >
        <!-- Cinematic scene: video → poster photo → CSS scene cascade, see
             public/media/home/hero/README.md for the asset contract. -->
        <HomeHeroMedia />

        <div
            class="relative mx-auto grid w-full max-w-7xl gap-16 px-4 py-28 pt-32 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:pt-28"
        >
            <div class="flex flex-col items-start gap-8">
                <span
                    class="inline-flex items-center gap-2 text-xs font-semibold tracking-[0.4em] text-fl-gold-soft uppercase"
                >
                    <span
                        class="h-px w-6 bg-fl-gold-soft/60"
                        aria-hidden="true"
                    />
                    Finisher Legacy
                </span>

                <h1
                    class="text-4xl leading-[1.05] font-black tracking-tight text-white sm:text-5xl lg:text-6xl"
                >
                    <template
                        v-for="(line, index) in title.split('\n')"
                        :key="index"
                    >
                        <span
                            class="fl-hero-line inline-block"
                            :class="{ 'text-fl-gold-soft': index === 1 }"
                            :style="{ animationDelay: `${index * 140}ms` }"
                            >{{ line }}</span
                        >
                        <br v-if="index < title.split('\n').length - 1" />
                    </template>
                </h1>

                <p class="max-w-lg text-lg leading-relaxed text-white/60">
                    {{ subtitle }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <MagneticButton>
                        <Button
                            as-child
                            size="lg"
                            class="bg-fl-gold px-8 text-base text-fl-black hover:bg-fl-gold-soft"
                        >
                            <Link :href="primaryHref">{{ primaryLabel }}</Link>
                        </Button>
                    </MagneticButton>
                    <Button
                        as-child
                        size="lg"
                        variant="outline"
                        class="border-white/30 bg-transparent px-8 text-base text-white hover:bg-white/5 hover:text-white"
                    >
                        <Link :href="secondaryHref">{{ secondaryLabel }}</Link>
                    </Button>
                </div>

                <p class="text-sm text-white/60">
                    Tu Legacy ID te acompaña carrera tras carrera.
                </p>

                <div
                    class="legacy-numeric flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-white/10 pt-5 text-sm text-white/40"
                >
                    <span
                        class="text-[10px] font-semibold tracking-[0.2em] text-white/25 uppercase"
                        >Vista previa</span
                    >
                    <span class="text-fl-gold-soft">42.195 KM</span>
                    <span aria-hidden="true">·</span>
                    <span>03:42:18</span>
                    <span aria-hidden="true">·</span>
                    <span>5:16 / KM</span>
                </div>
            </div>

            <div
                ref="stack"
                class="relative mx-auto w-full max-w-sm lg:mx-0 lg:ml-auto"
                style="--tilt-x: 0deg; --tilt-y: 0deg; perspective: 1200px"
                @pointermove="handlePointerMove"
                @pointerleave="resetTilt"
            >
                <div
                    class="absolute -inset-10 rounded-[2.5rem] bg-gradient-to-br from-fl-gold/20 via-transparent to-fl-gold-soft/10 blur-3xl"
                />

                <div
                    class="relative flex flex-col gap-4 transition-transform duration-300 ease-out"
                    style="
                        transform: rotateX(var(--tilt-x)) rotateY(var(--tilt-y));
                        transform-style: preserve-3d;
                    "
                >
                    <template v-for="(step, index) in chain" :key="step.label">
                        <div
                            class="flex items-center gap-4 rounded-2xl border bg-fl-graphite/70 px-5 py-4 backdrop-blur-sm transition-transform duration-300"
                            :class="
                                step.accent === 'gold-soft'
                                    ? 'border-fl-gold-soft/20'
                                    : 'border-fl-gold/25'
                            "
                            :style="{
                                transform: `translateZ(${(chain.length - index) * 6}px) translateX(${index * 10}px)`,
                            }"
                        >
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-full border"
                                :class="
                                    step.accent === 'gold-soft'
                                        ? 'border-fl-gold-soft/30 text-fl-gold-soft'
                                        : 'border-fl-gold/40 text-fl-gold-soft'
                                "
                            >
                                <component :is="step.icon" class="size-5" />
                            </div>
                            <span class="text-sm font-medium text-white/90">{{
                                step.label
                            }}</span>
                        </div>
                        <div
                            v-if="index < chain.length - 1"
                            class="legacy-line-v ml-9 h-6"
                        />
                    </template>
                </div>
            </div>
        </div>

        <div class="absolute inset-x-0 bottom-6 hidden justify-center sm:flex">
            <ScrollCueButton />
        </div>
    </section>
</template>

<style scoped>
/* Headline lines rise and clip in on load, staggered per line — a small
   entrance beat instead of the text just being static on paint. */
.fl-hero-line {
    animation: fl-hero-line-in 700ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes fl-hero-line-in {
    from {
        opacity: 0;
        transform: translateY(0.4em);
        clip-path: inset(0 0 100% 0);
    }
    to {
        opacity: 1;
        transform: translateY(0);
        clip-path: inset(0 0 0 0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .fl-hero-line {
        animation: none;
    }
}
</style>
