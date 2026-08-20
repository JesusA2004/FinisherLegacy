<script setup lang="ts">
/**
 * A phone-scanning scene instead of three icons in a row — the QR mosaic is
 * a stylized, hand-authored pattern (not a real scannable code) standing in
 * for photography until a real capture exists. Scan line + result reveal
 * make the "código → historia" idea legible almost without reading text
 * (brand system §15).
 */
import { UserRound } from '@lucide/vue';

withDefaults(
    defineProps<{
        sampleCode?: string;
    }>(),
    {
        sampleCode: 'FL-8K3XP7M',
    },
);

// Decorative QR-style mosaic — corner "finder" blocks + a scattered middle,
// authored by hand to read as a code without being a real one.
const qrPattern = [
    [1, 1, 1, 0, 1, 1, 1],
    [1, 0, 1, 0, 0, 0, 1],
    [1, 0, 1, 1, 1, 0, 1],
    [0, 0, 0, 1, 0, 1, 0],
    [1, 0, 1, 1, 0, 0, 1],
    [1, 0, 0, 0, 1, 0, 1],
    [1, 1, 1, 0, 1, 1, 1],
];
</script>

<template>
    <div class="flex flex-col items-center gap-6">
        <!-- Phone frame -->
        <div
            class="relative w-full max-w-[220px] rounded-[2rem] border-4 border-white/15 bg-fl-graphite/70 p-3 shadow-[0_20px_50px_-15px_rgba(0,0,0,0.6)]"
        >
            <div class="mx-auto mb-2 h-1.5 w-10 rounded-full bg-white/15" />

            <div
                class="relative flex aspect-[3/4] flex-col items-center justify-center gap-4 overflow-hidden rounded-[1.25rem] bg-fl-black px-4"
            >
                <div class="grid w-28 grid-cols-7 gap-[3px]" aria-hidden="true">
                    <span
                        v-for="(cell, index) in qrPattern.flat()"
                        :key="index"
                        class="aspect-square rounded-[1px]"
                        :class="cell ? 'bg-fl-gold-soft' : 'bg-white/5'"
                    />
                </div>

                <span
                    class="legacy-numeric rounded-md border border-white/15 bg-white/5 px-2 py-1 font-mono text-[11px] text-fl-gold-soft"
                >
                    {{ sampleCode }}
                </span>

                <span
                    class="fl-scan-line absolute inset-x-6 h-px bg-fl-gold-soft/80 motion-reduce:hidden"
                    aria-hidden="true"
                />
            </div>
        </div>

        <!-- Result: the code resolving into a Legacy Profile -->
        <div
            class="fl-scan-result flex items-center gap-3 rounded-full border border-fl-gold/30 bg-fl-graphite/50 py-2 pr-5 pl-2 motion-reduce:opacity-100"
        >
            <span
                class="flex size-9 items-center justify-center rounded-full border border-fl-gold-soft/30 text-fl-gold-soft"
            >
                <UserRound class="size-4" />
            </span>
            <span class="text-sm font-medium text-white/85">
                Tu Legacy Profile
            </span>
        </div>

        <p class="max-w-xs text-center text-sm text-white/50">
            Escanea el Legacy Code de tu placa y llega directo a tu historia.
        </p>
    </div>
</template>

<style scoped>
.fl-scan-line {
    animation: fl-scan 2.6s ease-in-out infinite;
}

@keyframes fl-scan {
    0%,
    100% {
        top: 18%;
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    50% {
        top: 82%;
        opacity: 1;
    }
    60% {
        opacity: 0;
    }
}

.fl-scan-result {
    animation: fl-scan-result-in 2.6s ease-in-out infinite;
}

@keyframes fl-scan-result-in {
    0%,
    45% {
        opacity: 0.35;
        transform: translateY(4px);
    }
    65%,
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .fl-scan-result {
        animation: none;
    }
}
</style>
