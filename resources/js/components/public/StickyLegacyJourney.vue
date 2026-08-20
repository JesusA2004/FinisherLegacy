<script setup lang="ts">
/**
 * MEDALLA → PLACA → LEGACY CODE → LEGACY PROFILE as one object evolving,
 * not four different shapes taking turns. A single frame (same metal
 * material, same light sweep, same pointer-tilt) morphs from a circular
 * medal into a rectangular plate — border-radius and aspect-ratio driven
 * directly by scroll progress, no CSS transition fighting the rAF loop —
 * while only the CONTENT inside crossfades per stage. ~300vh scroll region
 * with a pinned viewport. Replaces the old four-icon stepper
 * (JourneyExperience.vue) — that component is now unused; not deleted in
 * case another page wants the simpler static version later.
 *
 * Desktop only (lg+). Mobile gets a plain vertical sequence — sticky-pin
 * storytelling is a wide-viewport pattern, and forcing it into a narrow
 * screen fights "mobile no es desktop encogido" harder than it helps.
 * prefers-reduced-motion also gets the plain vertical sequence, fully
 * accessible with zero motion.
 */
import { Award, IdCard, Medal, QrCode } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue';
import Reveal from '@/components/motion/Reveal.vue';
import { useReducedMotion } from '@/composables/useReducedMotion';

const stages = [
    {
        icon: Medal,
        title: 'Medalla',
        copy: 'El logro comienza aquí.',
    },
    {
        icon: Award,
        title: 'Placa',
        copy: 'Convertimos los datos de esa carrera en una pieza creada para permanecer.',
    },
    {
        icon: QrCode,
        title: 'Legacy Code',
        copy: 'Una conexión permanente entre el objeto físico y su historia digital.',
    },
    {
        icon: IdCard,
        title: 'Legacy Profile',
        copy: 'Cada carrera pasa a formar parte de tu historia como atleta.',
    },
];

const prefersReducedMotion = useReducedMotion();
const isDesktop = useMediaQuery('(min-width: 1024px)');
const usesSticky = computed(
    () => isDesktop.value && !prefersReducedMotion.value,
);

const wrapperEl = useTemplateRef<HTMLElement>('wrapper');
const frameEl = useTemplateRef<HTMLElement>('frame');
const progress = ref(0); // 0..1 across the whole pinned region
let rafId: number | null = null;

function measure() {
    rafId = null;
    const el = wrapperEl.value;

    if (!el) {
        return;
    }

    const rect = el.getBoundingClientRect();
    const total = rect.height - window.innerHeight;
    const raw = total > 0 ? -rect.top / total : 0;
    progress.value = Math.min(1, Math.max(0, raw));
}

function onScroll() {
    if (rafId === null) {
        rafId = requestAnimationFrame(measure);
    }
}

onMounted(() => {
    if (usesSticky.value) {
        measure();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', onScroll);

    if (rafId !== null) {
        cancelAnimationFrame(rafId);
    }
});

// Which stage is active, and how far into it (0..1).
const stageFloat = computed(() => progress.value * stages.length);
const activeIndex = computed(() =>
    Math.min(stages.length - 1, Math.floor(stageFloat.value)),
);
const localT = computed(() => stageFloat.value - activeIndex.value);

// The frame itself is the thing that "becomes" a plate: a perfect circle
// (medal) morphing into a rounded rectangle (plate/QR/profile) across
// stage 0. No CSS transition on these — progress already updates every
// scroll frame via rAF, so a transition would only lag behind it.
const frameStyle = computed(() => {
    const inMedalStage = activeIndex.value === 0;
    const t = inMedalStage ? localT.value : 1;
    const radius = 999 - t * 967; // 999px (circle) → 32px (rounded-2xl)
    const aspect = 1 + t * 0.5; // 1/1 (circle) → 3/2 (plate)
    const wobble = Math.sin(stageFloat.value * 1.4) * 2.5;

    return {
        borderRadius: `${radius}px`,
        aspectRatio: `${aspect}`,
        transform: `rotate(${wobble}deg)`,
    };
});

// Content inside the frame crossfades between the 4 stages — no
// independent scale/rotate here, since the frame's own morph already
// carries the "one object transforming" read.
function contentStyle(index: number) {
    const isLast = index === stages.length - 1;
    const active = activeIndex.value;
    const t = localT.value;

    if (active === index) {
        const leave = isLast ? 0 : t;

        return {
            opacity: isLast ? 1 : 1 - leave,
            filter: `blur(${leave * 4}px)`,
        };
    }

    if (active === index - 1) {
        return { opacity: t, filter: `blur(${(1 - t) * 4}px)` };
    }

    return { opacity: 0, filter: 'blur(4px)' };
}

// Pointer tilt on the frame, on top of the scroll-driven shape morph — the
// same "product you can touch" feel the hero plate has.
function handlePointerMove(event: PointerEvent) {
    if (prefersReducedMotion.value || !frameEl.value) {
        return;
    }

    const rect = frameEl.value.getBoundingClientRect();
    const relX = (event.clientX - rect.left) / rect.width;
    const relY = (event.clientY - rect.top) / rect.height;
    frameEl.value.style.setProperty('--glare-x', `${relX * 100}%`);
    frameEl.value.style.setProperty('--glare-y', `${relY * 100}%`);
}
</script>

<template>
    <!-- Desktop / motion-enabled: sticky-pinned morph -->
    <div v-if="usesSticky" ref="wrapper" class="relative" style="height: 340vh">
        <div
            class="sticky top-16 flex h-[calc(100svh-4rem)] items-center overflow-hidden"
        >
            <div
                class="mx-auto grid w-full max-w-6xl grid-cols-2 items-center gap-16 px-4 sm:px-6 lg:px-8"
            >
                <!-- The object: one frame, evolving -->
                <div class="relative mx-auto w-full max-w-md">
                    <div
                        class="absolute -inset-16 rounded-full bg-gradient-to-br from-fl-gold/25 via-fl-gold-soft/10 to-transparent blur-3xl"
                        :style="{
                            opacity: activeIndex === 2 ? 0.7 : 0.4,
                        }"
                    />

                    <!-- Orbit ring — only reads while the frame is still a medal -->
                    <div
                        class="fl-orbit pointer-events-none absolute top-1/2 left-1/2 size-[120%] -translate-x-1/2 -translate-y-1/2 rounded-full border border-dashed border-fl-gold/25 transition-opacity duration-500"
                        :style="{
                            opacity: activeIndex === 0 ? 1 - localT : 0,
                        }"
                    />

                    <div
                        ref="frame"
                        class="fl-frame-sweep relative mx-auto w-full overflow-hidden border border-white/15 p-8 shadow-[0_30px_80px_-15px_rgba(0,0,0,0.75)] sm:p-10"
                        style="
                            --glare-x: 50%;
                            --glare-y: 30%;
                            background:
                                repeating-linear-gradient(
                                    100deg,
                                    rgba(255, 255, 255, 0.05) 0px,
                                    rgba(255, 255, 255, 0.05) 1px,
                                    transparent 1px,
                                    transparent 3px
                                ),
                                radial-gradient(
                                    circle at var(--glare-x) var(--glare-y),
                                    rgba(255, 255, 255, 0.16),
                                    transparent 45%
                                ),
                                linear-gradient(
                                    160deg,
                                    #3a3a3d 0%,
                                    #232326 55%,
                                    #17171a 100%
                                );
                        "
                        :style="frameStyle"
                        @pointermove="handlePointerMove"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-transparent"
                        />

                        <!-- Medal -->
                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3"
                            :style="contentStyle(0)"
                        >
                            <Medal class="size-16 text-fl-gold-soft" />
                            <span
                                class="text-xs font-semibold tracking-[0.3em] text-fl-gold-soft uppercase"
                                >El logro</span
                            >
                        </div>

                        <!-- Plate -->
                        <div
                            class="absolute inset-0 flex flex-col justify-between p-2"
                            :style="contentStyle(1)"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.35em] text-fl-gold-soft uppercase"
                            >
                                Finisher · Legacy
                            </p>
                            <div class="flex items-end justify-between">
                                <span
                                    class="legacy-numeric text-2xl font-bold text-white sm:text-3xl"
                                    >03:42:18</span
                                >
                                <Award class="size-8 text-fl-gold-soft" />
                            </div>
                        </div>

                        <!-- Legacy Code (QR) -->
                        <div
                            class="absolute inset-0 flex items-center justify-center"
                            :style="contentStyle(2)"
                        >
                            <div class="relative grid w-40 grid-cols-7 gap-1">
                                <span
                                    v-for="n in 49"
                                    :key="n"
                                    class="aspect-square rounded-[1px]"
                                    :class="
                                        [
                                            1, 5, 8, 12, 15, 20, 24, 27, 31, 36,
                                            40, 44, 47,
                                        ].includes(n)
                                            ? 'bg-fl-gold-soft'
                                            : 'bg-white/10'
                                    "
                                />
                                <span
                                    class="fl-qr-scan absolute inset-x-2 h-0.5 rounded-full bg-fl-gold-soft shadow-[0_0_10px_2px_rgba(224,202,137,0.8)]"
                                />
                            </div>
                        </div>

                        <!-- Legacy Profile -->
                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center gap-4"
                            :style="contentStyle(3)"
                        >
                            <div
                                class="flex size-14 items-center justify-center rounded-full border-2 border-fl-gold/40 bg-black/30 text-fl-gold-soft"
                            >
                                <IdCard class="size-6" />
                            </div>
                            <div
                                class="legacy-numeric text-sm font-semibold text-white"
                            >
                                12 medallas
                            </div>
                            <div class="h-1.5 w-40 rounded-full bg-white/15">
                                <div
                                    class="fl-profile-fill h-full rounded-full bg-gradient-to-r from-fl-gold to-fl-gold-soft"
                                    :style="{
                                        width:
                                            activeIndex === 3
                                                ? `${20 + localT * 60}%`
                                                : '20%',
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side text, synced to the active stage -->
                <div class="flex items-center gap-6">
                    <div
                        class="legacy-line-v h-48 shrink-0 self-center opacity-40"
                    >
                        <div
                            class="h-full w-full origin-top bg-gradient-to-b from-fl-gold via-fl-gold-soft to-transparent transition-transform duration-200"
                            :style="{ transform: `scaleY(${progress})` }"
                        />
                    </div>
                    <Transition name="fl-stage-fade" mode="out-in">
                        <div :key="activeIndex" class="min-w-0">
                            <span
                                class="legacy-numeric text-xs font-semibold text-white/40"
                            >
                                0{{ activeIndex + 1 }} / 0{{ stages.length }}
                            </span>
                            <h3
                                class="mt-2 text-4xl font-bold text-white lg:text-5xl"
                            >
                                {{ stages[activeIndex].title }}
                            </h3>
                            <p
                                class="mt-4 max-w-sm text-base leading-relaxed text-white/60 lg:text-lg"
                            >
                                {{ stages[activeIndex].copy }}
                            </p>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile / reduced-motion: plain vertical sequence -->
    <div
        v-else
        class="mx-auto flex max-w-3xl flex-col gap-10 px-4 py-24 sm:px-6 lg:px-8"
    >
        <Reveal
            v-for="(stage, index) in stages"
            :key="stage.title"
            :delay-ms="index * 90"
            class="flex items-start gap-4"
        >
            <div
                class="flex size-12 shrink-0 items-center justify-center rounded-full border border-fl-gold/40 bg-fl-graphite/60 text-fl-gold-soft"
            >
                <component :is="stage.icon" class="size-5" />
            </div>
            <div>
                <span class="legacy-numeric text-xs font-semibold text-white/40"
                    >0{{ index + 1 }}</span
                >
                <h3 class="mt-1 text-lg font-semibold text-white">
                    {{ stage.title }}
                </h3>
                <p class="mt-1 text-sm leading-relaxed text-white/60">
                    {{ stage.copy }}
                </p>
            </div>
        </Reveal>
    </div>
</template>

<style scoped>
.fl-orbit {
    animation: fl-orbit-spin 18s linear infinite;
}
@keyframes fl-orbit-spin {
    to {
        transform: translate(-50%, -50%) rotate(360deg);
    }
}

.fl-frame-sweep::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        115deg,
        transparent 35%,
        rgba(255, 255, 255, 0.14) 50%,
        transparent 65%
    );
    transform: translateX(-130%);
    animation: fl-frame-sweep 4.5s ease-in-out infinite;
    pointer-events: none;
}
@keyframes fl-frame-sweep {
    0%,
    35% {
        transform: translateX(-130%);
    }
    75%,
    100% {
        transform: translateX(130%);
    }
}

.fl-qr-scan {
    animation: fl-qr-scan 2.2s ease-in-out infinite;
}
@keyframes fl-qr-scan {
    0%,
    100% {
        top: 12%;
        opacity: 0;
    }
    15% {
        opacity: 1;
    }
    50% {
        top: 88%;
        opacity: 1;
    }
    65% {
        opacity: 0;
    }
}

.fl-profile-fill {
    transition: width 500ms ease-out;
}

.fl-stage-fade-enter-active,
.fl-stage-fade-leave-active {
    transition:
        opacity 220ms ease,
        transform 220ms ease;
}
.fl-stage-fade-enter-from {
    opacity: 0;
    transform: translateY(10px);
}
.fl-stage-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

@media (prefers-reduced-motion: reduce) {
    .fl-orbit,
    .fl-frame-sweep::before,
    .fl-qr-scan {
        animation: none;
    }
    .fl-stage-fade-enter-active,
    .fl-stage-fade-leave-active {
        transition: none;
    }
}
</style>
