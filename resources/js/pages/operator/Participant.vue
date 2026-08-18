<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref, watch } from 'vue';
import {
    generateIntegratedPlate,
    index,
    previewPlate as previewAction,
} from '@/actions/App/Http/Controllers/OperatorController';
import PlateResultPanel from '@/components/operator/PlateResultPanel.vue';
import PlatePreviewCard from '@/components/plates/PlatePreviewCard.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import type { PlateFace, PlateRenderMode } from '@/types/plate-studio';

type PlatePreviewData = {
    id: number;
    serial_number: string;
    athlete_name: string;
    legacy_code: string | null;
    qr_url: string | null;
    status: string;
};

const { participant, existingPlate, templateName } = defineProps<{
    participant: {
        id: number;
        bib_number: string | null;
        full_name: string;
        race: string | null;
        official_time: string | null;
        pace: string | null;
        result_status: string | null;
    };
    templateName: string | null;
    existingPlate: PlatePreviewData | null;
}>();

const generating = ref(false);

const face = ref<PlateFace>('front');
const mode = ref<PlateRenderMode>('product');
const svg = ref<string | null>(null);
const warnings = ref<string[]>([]);
const previewError = ref<string | null>(null);
const loading = ref(false);

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function fetchPreview() {
    // See admin/plates/Show.vue — a relative-URL fetch() during Inertia SSR
    // has no browser location to resolve against and crashes the render.
    if (import.meta.env.SSR) {
        return;
    }

    loading.value = true;

    try {
        if (existingPlate) {
            const response = await fetch(
                `/admin/plates/${existingPlate.id}/export/${face.value}/svg?mode=${mode.value}`,
            );
            svg.value = response.ok ? await response.text() : null;
            warnings.value = [];
            previewError.value = response.ok
                ? null
                : 'No se pudo generar la vista previa.';
        } else {
            const response = await fetch(previewAction().url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    event_participant_id: participant.id,
                    face: face.value,
                    mode: mode.value,
                }),
            });
            const json = await response.json();
            svg.value = json.svg;
            warnings.value = json.warnings ?? [];
            previewError.value = json.error ?? null;
        }
    } finally {
        loading.value = false;
    }
}

watch([face, mode], fetchPreview, { immediate: true });

function generate() {
    generating.value = true;
    router.post(
        generateIntegratedPlate(participant.id).url,
        {},
        { onFinish: () => (generating.value = false) },
    );
}
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

            <div class="mt-6 grid gap-8 sm:grid-cols-[1fr_360px]">
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
                    <p v-if="templateName" class="mt-1 text-xs text-white/40">
                        Molde: {{ templateName }}
                    </p>
                    <p v-else class="mt-1 text-xs text-amber-400">
                        Este evento no tiene un molde asignado — configúralo en
                        "Preparar evento para producción".
                    </p>

                    <div
                        class="mt-6 grid grid-cols-2 gap-4 rounded-xl border border-white/10 bg-fl-graphite/40 p-5"
                    >
                        <div>
                            <p class="text-xs text-white/40 uppercase">
                                Tiempo
                            </p>
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

                    <PlateResultPanel
                        v-if="existingPlate"
                        class="mt-6"
                        :plate="existingPlate"
                        :generate-another-href="index().url"
                    />
                    <Button
                        v-else
                        class="mt-6 w-full bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
                        :disabled="generating || !templateName"
                        @click="generate"
                    >
                        <Spinner v-if="generating" />
                        Confirmar y generar placa
                    </Button>
                </div>

                <div class="flex justify-center">
                    <PlatePreviewCard
                        v-model:face="face"
                        v-model:mode="mode"
                        :svg="svg"
                        :warnings="warnings"
                        :loading="loading"
                        :error="previewError"
                        :is-demo="!existingPlate"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
