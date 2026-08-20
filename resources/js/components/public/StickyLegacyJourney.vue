<script setup lang="ts">
/**
 * MEDALLA → PLACA → LEGACY CODE → LEGACY PROFILE as real sticky
 * storytelling: one ~300vh scroll region with a pinned viewport where the
 * central object morphs through the four stages and the side text
 * syncs to it. Replaces the old four-icon stepper (JourneyExperience.vue) —
 * that component is now unused; not deleted in case another page wants the
 * simpler static version later.
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

// Which stage is active, and how far into it (0..1) — drives the zoom /
// spin / blur settle on the shape and the fill of the side progress line.
const stageFloat = computed(() => progress.value * stages.length);
const activeIndex = computed(() =>
    Math.min(stages.length - 1, Math.floor(stageFloat.value)),
);
const localT = computed(() => stageFloat.value - activeIndex.value);

// One shared recipe for all 4 shapes instead of four near-identical inline
// ternary blocks: each shape is "entering" while the previous stage is in
// its back half, fully "settled" while its own stage is active, then
// "leaving" (spin + blur out) once the stage moves past it. The last shape
// (Legacy Profile) never leaves — it's the destination.
function shapeStyle(index: number) {
    const isLast = index === stages.length - 1;
    const active = activeIndex.value;
    const t = localT.value;

    if (active === index) {
        const leaveT = isLast ? 0 : t;

        return {
            opacity: isLast ? 1 : 1 - leaveT * 0.95,
            transform: `scale(${1 + leaveT * 0.35}) rotate(${leaveT * 10}deg)`,
            filter: `blur(${leaveT * 5}px)`,
        };
    }

    if (active === index - 1) {
        return {
            opacity: t,
            transform: `scale(${0.75 + t * 0.25}) rotate(${(1 - t) * -10}deg)`,
            filter: `blur(${(1 - t) * 5}px)`,
        };
    }

    return {
        opacity: 0,
        transform: 'scale(0.65) rotate(0deg)',
        filter: 'blur(6px)',
    };
}

// Ambient glow behind the object drifts and recolors slightly per stage —
// cheap way to make the whole scene feel alive between the shape morphs.
const glowStyle = computed(() => {
    const hueShift = stageFloat.value * 6; // deg, subtle

    return {
        transform: `translate(${Math.sin(stageFloat.value) * 6}%, ${Math.cos(stageFloat.value * 0.7) * 6}%) scale(${1 + Math.sin(stageFloat.value * 1.3) * 0.08})`,
        filter: `hue-rotate(${hueShift}deg)`,
    };
});
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
                <!-- Central morphing object -->
                <div
                    class="relative mx-auto flex size-80 items-center justify-center lg:size-[26rem] xl:size-[30rem]"
                    aria-hidden="true"
                >
                    <div
                        class="absolute inset-0 rounded-full bg-gradient-to-br from-fl-gold/25 via-fl-gold-soft/10 to-transparent blur-3xl transition-transform duration-500 ease-out"
                        :style="glowStyle"
                    />

                    <!-- Medal -->
                    <div
                        class="absolute inset-0 flex items-center justify-center transition-all duration-500 ease-out"
                        :style="shapeStyle(0)"
                    >
                        <div class="relative flex items-center justify-center">
                            <div
                                class="fl-orbit absolute size-64 rounded-full border border-dashed border-fl-gold/25 lg:size-72"
                            />
                            <div
                                class="fl-medal-pulse flex size-56 items-center justify-center rounded-full border-4 border-fl-gold/50 bg-gradient-to-br from-fl-graphite-light to-fl-black shadow-[0_0_80px_-10px_rgba(207,171,89,0.6)] lg:size-64"
                            >
                                <Medal
                                    class="size-20 text-fl-gold-soft lg:size-24"
                                />
                            </div>
                            <!-- Ribbon -->
                            <div
                                class="absolute top-[85%] left-1/2 flex -translate-x-1/2 gap-1"
                                aria-hidden="true"
                            >
                                <span
                                    class="h-16 w-6 bg-fl-gold/40"
                                    style="
                                        clip-path: polygon(
                                            0 0,
                                            100% 0,
                                            100% 85%,
                                            50% 100%,
                                            0 85%
                                        );
                                        transform: rotate(-8deg);
                                    "
                                />
                                <span
                                    class="h-16 w-6 bg-fl-gold-soft/30"
                                    style="
                                        clip-path: polygon(
                                            0 0,
                                            100% 0,
                                            100% 85%,
                                            50% 100%,
                                            0 85%
                                        );
                                        transform: rotate(8deg);
                                    "
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Plate -->
                    <div
                        class="absolute inset-0 flex items-center justify-center transition-all duration-500 ease-out"
                        :style="shapeStyle(1)"
                    >
                        <div
                            class="fl-shine fl-plate-shine relative flex aspect-[3/2] w-72 flex-col justify-between overflow-hidden rounded-2xl border border-white/15 bg-gradient-to-br from-[#3a3a3d] via-[#232326] to-[#17171a] p-6 shadow-[0_30px_70px_-15px_rgba(0,0,0,0.8)] lg:w-80"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-[0.3em] text-fl-gold-soft uppercase"
                            >
                                Finisher · Legacy
                            </p>
                            <div class="flex items-end justify-between">
                                <span
                                    class="legacy-numeric text-lg font-semibold text-white/80"
                                    >03:42:18</span
                                >
                                <Award class="size-10 text-fl-gold-soft" />
                            </div>
                        </div>
                    </div>

                    <!-- Legacy Code (QR) -->
                    <div
                        class="absolute inset-0 flex items-center justify-center transition-all duration-500 ease-out"
                        :style="shapeStyle(2)"
                    >
                        <div
                            class="relative grid w-56 grid-cols-7 gap-1 rounded-xl border border-white/10 bg-fl-black p-5 shadow-[0_0_60px_-10px_rgba(224,202,137,0.4)] lg:w-64"
                        >
                            <span
                                v-for="n in 49"
                                :key="n"
                                class="aspect-square rounded-[1px]"
                                :class="
                                    [
                                        1, 5, 8, 12, 15, 20, 24, 27, 31, 36, 40,
                                        44, 47,
                                    ].includes(n)
                                        ? 'bg-fl-gold-soft'
                                        : 'bg-white/5'
                                "
                            />
                            <span
                                class="fl-qr-scan absolute inset-x-3 h-0.5 rounded-full bg-fl-gold-soft shadow-[0_0_10px_2px_rgba(224,202,137,0.8)]"
                            />
                        </div>
                    </div>

                    <!-- Legacy Profile -->
                    <div
                        class="absolute inset-0 flex items-center justify-center transition-all duration-500 ease-out"
                        :style="shapeStyle(3)"
                    >
                        <div
                            class="flex w-64 flex-col items-center gap-4 rounded-2xl border border-white/10 bg-fl-graphite/70 p-8 shadow-[0_30px_70px_-15px_rgba(0,0,0,0.7)] lg:w-72"
                        >
                            <div
                                class="fl-medal-pulse flex size-16 items-center justify-center rounded-full border-2 border-fl-gold/40 bg-fl-black text-fl-gold-soft"
                            >
                                <IdCard class="size-7" />
                            </div>
                            <div
                                class="legacy-numeric text-base font-semibold text-white"
                            >
                                12 medallas
                            </div>
                            <div class="h-2 w-full rounded-full bg-white/10">
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
        transform: rotate(360deg);
    }
}

.fl-medal-pulse {
    animation: fl-medal-pulse 3.2s ease-in-out infinite;
}
@keyframes fl-medal-pulse {
    0%,
    100% {
        box-shadow: 0 0 0 0 color-mix(in srgb, var(--fl-gold) 35%, transparent);
    }
    50% {
        box-shadow:
            0 0 0 14px transparent,
            0 0 40px -4px color-mix(in srgb, var(--fl-gold) 25%, transparent);
    }
}

.fl-plate-shine::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        115deg,
        transparent 30%,
        rgba(255, 255, 255, 0.16) 48%,
        transparent 66%
    );
    transform: translateX(-130%);
    animation: fl-plate-sweep 3.6s ease-in-out infinite;
}
@keyframes fl-plate-sweep {
    0%,
    30% {
        transform: translateX(-130%);
    }
    70%,
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
    .fl-medal-pulse,
    .fl-plate-shine::before,
    .fl-qr-scan {
        animation: none;
    }
    .fl-stage-fade-enter-active,
    .fl-stage-fade-leave-active {
        transition: none;
    }
}
</style>
