<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarClock, QrCode, Search, Zap } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import {
    generateQuickPlate,
    previewPlate as previewAction,
    search as searchAction,
    selectEvent,
    showParticipant,
} from '@/actions/App/Http/Controllers/OperatorController';
import Reveal from '@/components/motion/Reveal.vue';
import PlatePreviewCard from '@/components/plates/PlatePreviewCard.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { PlateFace, PlateRenderMode } from '@/types/plate-studio';

type SearchResult = {
    id: number;
    bib_number: string | null;
    full_name: string;
    race: string | null;
    has_result: boolean;
    has_plate: boolean;
};

const { editions, activeEdition } = defineProps<{
    editions: { id: number; name: string }[];
    activeEdition: { id: number; name: string; hasTemplate: boolean } | null;
}>();

const query = ref('');
const results = ref<SearchResult[]>([]);
const searching = ref(false);
const quickPlateOpen = ref(false);

const quickForm = {
    athlete_name: ref(''),
    bib_number: ref(''),
    race_name: ref(''),
    official_time: ref(''),
    pace: ref(''),
    swim_time: ref(''),
    bike_time: ref(''),
    run_time: ref(''),
    personal_phrase: ref(''),
};
const submittingQuick = ref(false);

const face = ref<PlateFace>('front');
const mode = ref<PlateRenderMode>('product');
const previewSvg = ref<string | null>(null);
const previewWarnings = ref<string[]>([]);
const previewError = ref<string | null>(null);
const previewLoading = ref(false);

function csrfToken(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

async function fetchQuickPreview() {
    if (!quickPlateOpen.value) {
        return;
    }

    previewLoading.value = true;

    try {
        const response = await fetch(previewAction().url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                quick: {
                    athlete_name: quickForm.athlete_name.value,
                    bib_number: quickForm.bib_number.value || null,
                    race_name: quickForm.race_name.value || null,
                    official_time: quickForm.official_time.value || null,
                    pace: quickForm.pace.value || null,
                    swim_time: quickForm.swim_time.value || null,
                    bike_time: quickForm.bike_time.value || null,
                    run_time: quickForm.run_time.value || null,
                    personal_phrase: quickForm.personal_phrase.value || null,
                },
                face: face.value,
                mode: mode.value,
            }),
        });
        const json = await response.json();
        previewSvg.value = json.svg;
        previewWarnings.value = json.warnings ?? [];
        previewError.value = json.error ?? null;
    } finally {
        previewLoading.value = false;
    }
}

const debouncedPreview = useDebounceFn(fetchQuickPreview, 300);

watch([...Object.values(quickForm), face, mode, quickPlateOpen], () =>
    debouncedPreview(),
);

async function runSearch() {
    if (!query.value.trim()) {
        results.value = [];

        return;
    }

    searching.value = true;
    const response = await fetch(
        `${searchAction().url}?q=${encodeURIComponent(query.value)}`,
    );
    results.value = (await response.json()).data ?? [];
    searching.value = false;
}

watch(query, useDebounceFn(runSearch, 250));

function onSelectEvent(value: unknown) {
    if (!value) {
        return;
    }

    router.post(
        selectEvent().url,
        { event_edition_id: String(value) },
        { preserveScroll: true },
    );
}

function submitQuickPlate() {
    submittingQuick.value = true;
    router.post(
        generateQuickPlate().url,
        {
            athlete_name: quickForm.athlete_name.value,
            bib_number: quickForm.bib_number.value || null,
            race_name: quickForm.race_name.value || null,
            official_time: quickForm.official_time.value || null,
            pace: quickForm.pace.value || null,
            swim_time: quickForm.swim_time.value || null,
            bike_time: quickForm.bike_time.value || null,
            run_time: quickForm.run_time.value || null,
            personal_phrase: quickForm.personal_phrase.value || null,
        },
        {
            onFinish: () => {
                submittingQuick.value = false;
                quickPlateOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Event OS — Operador" />

    <div class="min-h-svh bg-fl-black p-4 md:p-8">
        <div class="mx-auto max-w-2xl">
            <div class="mb-6 flex items-center justify-between gap-4">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.2em] text-fl-gold uppercase"
                    >
                        Event OS
                    </p>
                    <h1 class="text-2xl font-bold text-white">Operador</h1>
                </div>
                <Select
                    :model-value="String(activeEdition?.id ?? '')"
                    @update:model-value="onSelectEvent"
                >
                    <SelectTrigger
                        class="fl-focus-glow h-12 w-64 border-white/10 bg-fl-graphite/60 text-white transition-colors hover:border-fl-gold/40"
                    >
                        <SelectValue placeholder="Selecciona un evento" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="edition in editions"
                            :key="edition.id"
                            :value="String(edition.id)"
                        >
                            {{ edition.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <template v-if="!activeEdition">
                <Reveal>
                    <div
                        class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-white/15 bg-fl-graphite/20 p-14 text-center"
                    >
                        <div
                            class="flex size-14 items-center justify-center rounded-full border border-fl-gold/25 bg-fl-black"
                        >
                            <CalendarClock class="size-7 text-fl-gold" />
                        </div>
                        <p class="max-w-xs text-sm text-white/50">
                            Selecciona un evento activo para empezar a buscar
                            corredores y generar placas.
                        </p>
                    </div>
                </Reveal>
            </template>

            <template v-else>
                <p
                    v-if="!activeEdition.hasTemplate"
                    class="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-xs text-amber-400"
                >
                    Este evento no tiene un molde de placa asignado. Configúralo
                    en "Preparar evento para producción" antes de generar
                    placas.
                </p>

                <p
                    class="mb-2 text-xs font-semibold tracking-[0.2em] text-white/40 uppercase"
                >
                    Buscar corredor
                </p>
                <div class="relative mb-3">
                    <Search
                        class="absolute top-1/2 left-4 size-5 -translate-y-1/2 text-white/40"
                    />
                    <Input
                        v-model="query"
                        autofocus
                        placeholder="Número de corredor o nombre…"
                        class="fl-focus-glow h-14 border-white/10 bg-fl-graphite/60 pl-12 text-lg text-white placeholder:text-white/30"
                        @keyup.enter="runSearch"
                    />
                </div>

                <Button
                    variant="outline"
                    class="fl-focus-glow h-12 w-full gap-2 border-fl-gold/30 text-sm font-semibold tracking-wide text-fl-gold uppercase hover:bg-fl-gold/10"
                    @click="quickPlateOpen = true"
                >
                    <Zap class="size-4" />
                    Generar placa rápida
                </Button>
                <p class="mt-1.5 mb-6 text-xs text-white/30">
                    Para corredores sin cuenta o sin resultado en el sistema
                    todavía. La placa se genera igual, con su propio Legacy Code
                    permanente — la persona puede reclamarla después escaneando
                    el QR.
                </p>

                <div
                    v-if="searching"
                    class="flex items-center gap-2 py-4 text-sm text-white/40"
                >
                    <Spinner /> Buscando…
                </div>

                <div v-else-if="results.length" class="space-y-2">
                    <Link
                        v-for="participant in results"
                        :key="participant.id"
                        :href="showParticipant(participant.id).url"
                        class="fl-hover-glow flex items-center justify-between rounded-xl border border-white/10 bg-fl-graphite/40 p-4 transition-colors"
                    >
                        <div>
                            <p class="font-semibold text-white">
                                {{ participant.full_name }}
                            </p>
                            <p class="text-xs text-white/50">
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
                        </div>
                        <span
                            v-if="participant.has_plate"
                            class="rounded-full border border-fl-gold/30 px-2.5 py-1 text-[10px] text-fl-gold uppercase"
                            >Ya tiene placa</span
                        >
                    </Link>
                </div>

                <p
                    v-else-if="query.trim().length > 0"
                    class="py-6 text-center text-sm text-white/30"
                >
                    Sin corredores para «{{ query }}». Prueba con otro nombre o
                    número, o genera una placa rápida.
                </p>
            </template>
        </div>

        <Dialog v-model:open="quickPlateOpen">
            <DialogContent
                class="dark max-w-3xl border-white/10 bg-fl-graphite text-white"
            >
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <QrCode class="size-5 text-fl-gold" /> Placa rápida
                    </DialogTitle>
                </DialogHeader>
                <div class="grid gap-6 sm:grid-cols-[1fr_260px]">
                    <form class="space-y-4" @submit.prevent="submitQuickPlate">
                        <div class="grid gap-2">
                            <Label>Nombre</Label>
                            <Input
                                v-model="quickForm.athlete_name.value"
                                required
                                class="bg-fl-black"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Número (opcional)</Label>
                                <Input
                                    v-model="quickForm.bib_number.value"
                                    class="bg-fl-black"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Distancia (opcional)</Label>
                                <Input
                                    v-model="quickForm.race_name.value"
                                    class="bg-fl-black"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="grid gap-2">
                                <Label>Tiempo (si se tiene)</Label>
                                <Input
                                    v-model="quickForm.official_time.value"
                                    class="bg-fl-black"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Ritmo (si se tiene)</Label>
                                <Input
                                    v-model="quickForm.pace.value"
                                    class="bg-fl-black"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="grid gap-2">
                                <Label class="text-xs">Natación</Label>
                                <Input
                                    v-model="quickForm.swim_time.value"
                                    class="bg-fl-black"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label class="text-xs">Ciclismo</Label>
                                <Input
                                    v-model="quickForm.bike_time.value"
                                    class="bg-fl-black"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label class="text-xs">Carrera</Label>
                                <Input
                                    v-model="quickForm.run_time.value"
                                    class="bg-fl-black"
                                />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label
                                >Frase personal (opcional, va en el
                                reverso)</Label
                            >
                            <Input
                                v-model="quickForm.personal_phrase.value"
                                class="bg-fl-black"
                                maxlength="150"
                            />
                        </div>
                        <DialogFooter>
                            <Button
                                type="submit"
                                class="w-full bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
                                :disabled="submittingQuick"
                            >
                                <Spinner v-if="submittingQuick" />
                                Generar placa
                            </Button>
                        </DialogFooter>
                    </form>

                    <div class="flex justify-center">
                        <PlatePreviewCard
                            v-model:face="face"
                            v-model:mode="mode"
                            :svg="previewSvg"
                            :warnings="previewWarnings"
                            :loading="previewLoading"
                            :error="previewError"
                            is-demo
                        />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
