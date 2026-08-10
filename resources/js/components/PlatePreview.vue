<script setup lang="ts">
/**
 * Renders from the exact same payload shape the backend produces via
 * PlateGenerationService::previewPayload() — frontend and backend never
 * calculate the plate's contents independently.
 */
defineProps<{
    plate: {
        serial_number: string;
        athlete_name: string;
        bib_number: string | null;
        event_name: string | null;
        race_name: string | null;
        official_time: string | null;
        pace: string | null;
        event_date: string | null;
        legacy_code: string | null;
        qr_url: string | null;
    };
}>();
</script>

<template>
    <div
        class="relative aspect-[10/14] w-full max-w-xs overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-[#2a2a2e] via-[#1a1a1c] to-[#0b0b0c] p-6 shadow-2xl"
        style="
            background-image:
                repeating-linear-gradient(
                    115deg,
                    rgba(255, 255, 255, 0.03) 0px,
                    rgba(255, 255, 255, 0.03) 1px,
                    transparent 1px,
                    transparent 3px
                ),
                linear-gradient(135deg, #2a2a2e, #1a1a1c 60%, #0b0b0c);
        "
    >
        <div class="flex h-full flex-col justify-between">
            <div>
                <p class="text-xl leading-tight font-bold text-fl-gold">
                    {{ plate.athlete_name }}
                </p>
                <p v-if="plate.event_name" class="mt-2 text-sm text-white/80">
                    {{ plate.event_name }}
                </p>
                <p v-if="plate.race_name" class="text-xs text-white/50">
                    {{ plate.race_name }}
                </p>
            </div>

            <div>
                <p
                    v-if="plate.official_time"
                    class="font-mono text-3xl font-bold text-white"
                >
                    {{ plate.official_time }}
                </p>
                <p v-if="plate.pace" class="text-xs text-white/50">
                    Ritmo {{ plate.pace }}
                </p>
            </div>

            <div class="flex items-end justify-between">
                <div>
                    <p
                        v-if="plate.bib_number"
                        class="font-mono text-xs text-white/40"
                    >
                        #{{ plate.bib_number }}
                    </p>
                    <p class="font-mono text-[10px] text-white/30">
                        {{ plate.serial_number }}
                    </p>
                </div>
                <div
                    v-if="plate.qr_url"
                    class="size-14 shrink-0 rounded-md bg-white p-1"
                >
                    <img :src="plate.qr_url" alt="Código QR" class="size-full" />
                </div>
            </div>
        </div>
    </div>
</template>
