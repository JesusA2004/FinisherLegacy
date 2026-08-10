<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2 } from '@lucide/vue';
import { ref } from 'vue';
import { generateIntegratedPlate, index } from '@/actions/App/Http/Controllers/OperatorController';
import PlatePreview from '@/components/PlatePreview.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type PlatePreviewData = {
    serial_number: string;
    athlete_name: string;
    bib_number: string | null;
    event_name: string | null;
    race_name: string | null;
    official_time: string | null;
    pace: string | null;
    event_date: string | null;
    status: string;
    legacy_code: string | null;
    qr_url: string | null;
};

const { participant, existingPlate } = defineProps<{
    participant: {
        id: number;
        bib_number: string | null;
        full_name: string;
        race: string | null;
        official_time: string | null;
        pace: string | null;
        result_status: string | null;
    };
    existingPlate: PlatePreviewData | null;
}>();

const generating = ref(false);

function generate() {
    generating.value = true;
    router.post(
        generateIntegratedPlate(participant.id).url,
        {},
        { onFinish: () => (generating.value = false) },
    );
}

const previewPlate: PlatePreviewData =
    existingPlate ?? {
        serial_number: 'PLT-XXXXXXXX',
        athlete_name: participant.full_name,
        bib_number: participant.bib_number,
        event_name: null,
        race_name: participant.race,
        official_time: participant.official_time,
        pace: participant.pace,
        event_date: null,
        status: 'draft',
        legacy_code: null,
        qr_url: null,
    };
</script>

<template>
    <Head :title="participant.full_name" />

    <div class="min-h-svh p-4 md:p-8">
        <div class="mx-auto max-w-4xl">
            <Link
                :href="index().url"
                class="inline-flex items-center gap-1.5 text-sm text-white/50 hover:text-white"
            >
                <ArrowLeft class="size-4" /> Volver a la búsqueda
            </Link>

            <div class="mt-6 grid gap-8 sm:grid-cols-[1fr_320px]">
                <div>
                    <h1 class="text-2xl font-bold text-white">
                        {{ participant.full_name }}
                    </h1>
                    <p class="mt-1 text-sm text-white/50">
                        {{
                            [
                                participant.bib_number
                                    ? `#${participant.bib_number}`
                                    : null,
                                participant.race,
                            ]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </p>

                    <div
                        class="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-white/10 bg-fl-graphite/40 p-5"
                    >
                        <div>
                            <p class="text-xs text-white/40 uppercase">Tiempo</p>
                            <p class="font-mono text-lg text-fl-gold">
                                {{ participant.official_time ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-white/40 uppercase">Ritmo</p>
                            <p class="text-lg text-white">
                                {{ participant.pace ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <div v-if="existingPlate" class="mt-6 flex items-center gap-2 rounded-xl border border-fl-gold/20 bg-fl-gold/5 p-4 text-sm text-fl-gold">
                        <CheckCircle2 class="size-4" />
                        Placa generada — Legacy Code {{ existingPlate.legacy_code }}
                    </div>
                    <Button
                        v-else
                        class="mt-6 w-full bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
                        :disabled="generating"
                        @click="generate"
                    >
                        <Spinner v-if="generating" />
                        Confirmar y generar placa
                    </Button>
                </div>

                <div class="flex justify-center">
                    <PlatePreview :plate="previewPlate" />
                </div>
            </div>
        </div>
    </div>
</template>
