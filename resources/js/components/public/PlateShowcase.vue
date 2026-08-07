<script setup lang="ts">
import { QrCode } from '@lucide/vue';

withDefaults(
    defineProps<{
        eventName?: string;
        athleteName?: string;
        time?: string;
        serial?: string;
    }>(),
    {
        eventName: 'TU EVENTO AQUÍ',
        athleteName: 'TU NOMBRE',
        time: '00:00:00',
        serial: '#FL-0000000',
    },
);
</script>

<template>
    <div
        class="relative mx-auto aspect-[3/2] w-full max-w-sm rounded-xl border border-white/10 p-6 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.6)]"
        style="
            background:
                repeating-linear-gradient(
                    100deg,
                    rgba(255, 255, 255, 0.05) 0px,
                    rgba(255, 255, 255, 0.05) 1px,
                    transparent 1px,
                    transparent 3px
                ),
                linear-gradient(160deg, #3a3a3d 0%, #232326 55%, #17171a 100%);
        "
    >
        <div
            class="pointer-events-none absolute inset-0 rounded-xl bg-gradient-to-br from-white/10 via-transparent to-transparent"
        />

        <div class="relative flex h-full flex-col justify-between">
            <div>
                <p
                    class="text-[10px] font-semibold tracking-[0.3em] text-fl-gold uppercase"
                >
                    Finisher · Legacy
                </p>
                <p
                    class="mt-3 text-lg leading-tight font-bold tracking-tight text-white uppercase"
                >
                    {{ eventName }}
                </p>
                <p class="mt-1 text-sm text-white/70">{{ athleteName }}</p>
                <p class="mt-2 font-mono text-xl font-semibold text-fl-gold">
                    {{ time }}
                </p>
            </div>

            <div class="flex items-end justify-between">
                <span class="font-mono text-[10px] text-white/40">{{
                    serial
                }}</span>
                <div
                    class="flex size-9 items-center justify-center rounded-md border border-white/15 bg-black/30"
                >
                    <QrCode class="size-5 text-white/70" />
                </div>
            </div>
        </div>
    </div>
</template>
