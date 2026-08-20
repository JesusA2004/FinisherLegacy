<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Award, IdCard, Medal, QrCode } from '@lucide/vue';
import { useTemplateRef } from 'vue';
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
    { icon: Medal, label: 'Medalla física', accent: 'copper' as const },
    { icon: Award, label: 'Placa Legacy', accent: 'copper' as const },
    { icon: QrCode, label: 'Legacy Code', accent: 'ice' as const },
    { icon: IdCard, label: 'Legacy Profile', accent: 'ice' as const },
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
    <section class="relative flex min-h-[100svh] items-center overflow-hidden bg-legacy-ink">
        <!-- Cinematic scene: track lanes + copper dawn light, standing in for
             photography until real event/finish-line assets are dropped in. -->
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute inset-0"
                style="
                    background:
                        radial-gradient(ellipse 70% 55% at 18% 15%, color-mix(in srgb, var(--legacy-copper) 22%, transparent), transparent 60%),
                        radial-gradient(ellipse 60% 50% at 85% 85%, color-mix(in srgb, var(--legacy-ice) 12%, transparent), transparent 60%),
                        linear-gradient(180deg, var(--legacy-ink) 0%, var(--legacy-ink-soft) 55%, var(--legacy-ink) 100%);
                "
            />
            <div
                class="absolute inset-0 opacity-[0.07]"
                style="
                    background-image: repeating-linear-gradient(
                        115deg,
                        var(--legacy-titanium) 0px,
                        var(--legacy-titanium) 1px,
                        transparent 1px,
                        transparent 64px
                    );
                "
            />
            <div class="absolute inset-x-0 bottom-0 h-px bg-legacy-titanium/10" />
        </div>

        <div
            class="relative mx-auto grid w-full max-w-7xl gap-16 px-4 py-28 pt-32 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8 lg:pt-28"
        >
            <div class="flex flex-col items-start gap-8">
                <span
                    class="inline-flex items-center gap-2 text-xs font-semibold tracking-[0.4em] text-legacy-copper-soft uppercase"
                >
                    <span class="h-px w-6 bg-legacy-copper-soft/60" aria-hidden="true" />
                    Finisher Legacy
                </span>

                <h1
                    class="text-4xl leading-[1.05] font-black tracking-tight text-legacy-bone sm:text-5xl lg:text-6xl"
                >
                    <template
                        v-for="(line, index) in title.split('\n')"
                        :key="index"
                    >
                        <span :class="{ 'text-legacy-copper-soft': index === 1 }">{{
                            line
                        }}</span>
                        <br v-if="index < title.split('\n').length - 1" />
                    </template>
                </h1>

                <p class="max-w-lg text-lg leading-relaxed text-legacy-titanium">
                    {{ subtitle }}
                </p>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Button
                        as-child
                        size="lg"
                        class="bg-legacy-copper px-8 text-base text-legacy-bone hover:bg-legacy-copper-soft"
                    >
                        <Link :href="primaryHref">{{ primaryLabel }}</Link>
                    </Button>
                    <Button
                        as-child
                        size="lg"
                        variant="outline"
                        class="border-legacy-titanium/30 bg-transparent px-8 text-base text-legacy-bone hover:bg-white/5 hover:text-legacy-bone"
                    >
                        <Link :href="secondaryHref">{{ secondaryLabel }}</Link>
                    </Button>
                </div>

                <p class="text-sm text-legacy-titanium/60">
                    Tu Legacy ID te acompaña carrera tras carrera.
                </p>
            </div>

            <div
                ref="stack"
                class="relative mx-auto w-full max-w-sm lg:mx-0 lg:ml-auto"
                style="--tilt-x: 0deg; --tilt-y: 0deg; perspective: 1200px"
                @pointermove="handlePointerMove"
                @pointerleave="resetTilt"
            >
                <div
                    class="absolute -inset-10 rounded-[2.5rem] bg-gradient-to-br from-legacy-copper/20 via-transparent to-legacy-ice/10 blur-3xl"
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
                            class="flex items-center gap-4 rounded-2xl border bg-legacy-carbon/70 px-5 py-4 backdrop-blur-sm transition-transform duration-300"
                            :class="
                                step.accent === 'ice'
                                    ? 'border-legacy-ice/20'
                                    : 'border-legacy-copper/25'
                            "
                            :style="{
                                transform: `translateZ(${(chain.length - index) * 6}px) translateX(${index * 10}px)`,
                            }"
                        >
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-full border"
                                :class="
                                    step.accent === 'ice'
                                        ? 'border-legacy-ice/30 text-legacy-ice'
                                        : 'border-legacy-copper/40 text-legacy-copper-soft'
                                "
                            >
                                <component :is="step.icon" class="size-5" />
                            </div>
                            <span class="text-sm font-medium text-legacy-bone/90">{{
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

        <div
            class="absolute inset-x-0 bottom-6 hidden justify-center sm:flex"
            aria-hidden="true"
        >
            <div class="flex flex-col items-center gap-2 text-legacy-titanium/40">
                <span class="text-[10px] font-semibold tracking-[0.3em] uppercase">Descubre más</span>
                <div class="h-8 w-px bg-gradient-to-b from-legacy-titanium/50 to-transparent" />
            </div>
        </div>
    </section>
</template>
