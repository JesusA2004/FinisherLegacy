<script setup lang="ts">
/**
 * Full-bleed cinematic hero: video/CSS scene as the only visual, no product
 * mockup competing with it (StickyLegacyJourney is where the plate/QR/
 * profile actually get shown, right below). Single column so the video
 * carries the scene the way a real campaign hero does, not a two-column
 * SaaS layout with a card floating on the side.
 */
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import MagneticButton from '@/components/public/MagneticButton.vue';
import HomeHeroMedia from '@/components/public/media/HomeHeroMedia.vue';
import ScrollCueButton from '@/components/public/ScrollCueButton.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    title: string;
    subtitle: string;
    primaryLabel: string;
    primaryHref: NonNullable<InertiaLinkProps['href']>;
    secondaryLabel: string;
    secondaryHref: NonNullable<InertiaLinkProps['href']>;
}>();
</script>

<template>
    <section
        class="relative flex min-h-[100svh] items-center overflow-hidden bg-fl-black"
    >
        <!-- Cinematic scene: video → poster photo → CSS scene cascade, see
             public/media/home/hero/README.md for the asset contract. -->
        <HomeHeroMedia />

        <!-- Giant background numerals — art direction, not a data table
             (brand system §23.5): the race stats become part of the scene
             instead of sitting in a tidy row. -->
        <div
            class="legacy-numeric pointer-events-none absolute inset-0 hidden overflow-hidden opacity-[0.07] select-none lg:block"
            aria-hidden="true"
        >
            <span
                class="absolute -top-10 -right-16 text-[16rem] leading-none font-black tracking-tighter text-white xl:text-[20rem]"
                >42.195</span
            >
            <span
                class="absolute bottom-10 -left-10 text-[9rem] leading-none font-black tracking-tighter text-white xl:text-[11rem]"
                >03:42:18</span
            >
        </div>

        <div
            class="relative mx-auto w-full max-w-4xl px-4 py-28 pt-32 sm:px-6 lg:px-8"
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
                    class="leading-[0.95] font-black tracking-tight text-white"
                    style="font-size: clamp(3rem, 8vw, 8rem)"
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

                <p class="max-w-lg text-lg leading-relaxed text-white/70">
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
