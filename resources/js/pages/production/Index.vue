<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight, Ban, Check, Download, PackageOpen } from '@lucide/vue';
import { ref } from 'vue';
import { exportMethod as exportFace } from '@/actions/App/Http/Controllers/Admin/PlateController';
import {
    updateChecklist,
    updateStatus,
} from '@/actions/App/Http/Controllers/ProductionController';
import HelpPopover from '@/components/HelpPopover.vue';
import { Spinner } from '@/components/ui/spinner';

type ChecklistItem = 'front' | 'back' | 'qr';

type Card = {
    id: number;
    serial_number: string;
    athlete_name: string;
    bib_number: string | null;
    event_name: string | null;
    status: string;
    legacy_code: string | null;
    generation_mode: string;
    updated_at: string | null;
    download_format: string;
    download_dpi: number;
    checklist: Record<ChecklistItem, boolean>;
};

const checklistLabels: Record<ChecklistItem, string> = {
    front: 'Frente',
    back: 'Reverso',
    qr: 'QR',
};

function quickDownloadUrl(card: Card): string {
    return exportFace.url([card.id, 'front', card.download_format], {
        query:
            card.download_format === 'png'
                ? { dpi: String(card.download_dpi) }
                : {},
    });
}

const { columns } = defineProps<{
    columns: Record<
        'pending' | 'processing' | 'ready' | 'delivered' | 'issue',
        Card[]
    >;
}>();

const columnMeta: Record<
    string,
    { title: string; next: string | null; nextLabel: string; accent: string }
> = {
    pending: {
        title: 'Pendiente',
        next: 'processing',
        nextLabel: 'Iniciar producción',
        accent: 'bg-white/30',
    },
    processing: {
        title: 'En proceso',
        next: 'ready',
        nextLabel: 'Marcar lista',
        accent: 'bg-amber-400',
    },
    ready: {
        title: 'Lista',
        next: 'delivered',
        nextLabel: 'Marcar entregada',
        accent: 'bg-sky-400',
    },
    delivered: {
        title: 'Entregada',
        next: null,
        nextLabel: '',
        accent: 'bg-emerald-400',
    },
    issue: {
        title: 'Incidencia',
        next: null,
        nextLabel: '',
        accent: 'bg-red-400',
    },
};

const pendingIds = ref(new Set<number>());

function withPending(plateId: number, status: string) {
    pendingIds.value.add(plateId);
    router.patch(
        updateStatus(plateId).url,
        { status },
        {
            preserveScroll: true,
            onFinish: () => pendingIds.value.delete(plateId),
        },
    );
}

function advance(plateId: number, next: string) {
    withPending(plateId, next);
}

function cancel(plateId: number) {
    withPending(plateId, 'cancelled');
}

function toggleChecklist(card: Card, item: ChecklistItem) {
    router.patch(
        updateChecklist(card.id).url,
        { item, checked: !card.checklist[item] },
        { preserveScroll: true },
    );
}

function checklistComplete(card: Card): boolean {
    return card.checklist.front && card.checklist.back && card.checklist.qr;
}
</script>

<template>
    <Head title="Producción" />

    <div class="min-h-svh p-4 md:p-6">
        <h1 class="mb-6 flex items-center gap-1.5 text-xl font-bold text-white">
            Producción
            <HelpPopover
                title="Archivo para láser"
                text="SVG es el formato recomendado para importar al software de la máquina láser: es vectorial, así que nunca pierde nitidez sin importar el tamaño físico de la placa."
            />
        </h1>

        <div
            class="grid grid-cols-1 gap-4 overflow-x-auto sm:grid-cols-2 lg:grid-cols-5"
        >
            <div
                v-for="key in [
                    'pending',
                    'processing',
                    'ready',
                    'delivered',
                    'issue',
                ] as const"
                :key="key"
                class="rounded-xl border border-white/10 bg-fl-graphite/30 p-3"
            >
                <p
                    class="mb-3 flex items-center gap-1.5 text-xs font-semibold tracking-wide text-white/50 uppercase"
                >
                    <span
                        class="size-1.5 rounded-full"
                        :class="columnMeta[key].accent"
                    />
                    {{ columnMeta[key].title }}
                    <span class="text-white/30"
                        >({{ columns[key]?.length ?? 0 }})</span
                    >
                </p>

                <TransitionGroup
                    tag="div"
                    class="relative space-y-2"
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-2 scale-95"
                    leave-active-class="absolute w-full transition duration-200 ease-in"
                    leave-to-class="opacity-0 translate-x-3 scale-95"
                    move-class="transition duration-300 ease-out"
                >
                    <div
                        v-for="card in columns[key]"
                        :key="card.id"
                        class="fl-hover-lift rounded-lg border border-white/10 bg-fl-black/60 p-3 transition-colors hover:border-fl-gold/25"
                        :class="{ 'opacity-50': pendingIds.has(card.id) }"
                    >
                        <p class="truncate text-sm font-medium text-white">
                            {{ card.athlete_name }}
                        </p>
                        <p class="truncate text-xs text-white/40">
                            {{
                                [
                                    card.bib_number
                                        ? `#${card.bib_number}`
                                        : null,
                                    card.event_name,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                        <div class="mt-1 flex items-center justify-between">
                            <p class="font-mono text-[10px] text-white/30">
                                {{ card.serial_number }}
                            </p>
                            <a
                                :href="quickDownloadUrl(card)"
                                :title="`Descargar ${card.download_format.toUpperCase()} (frente)`"
                                class="text-white/30 hover:text-fl-gold"
                                target="_blank"
                            >
                                <Download class="size-3" />
                            </a>
                        </div>

                        <div
                            v-if="key === 'processing'"
                            class="mt-2 flex gap-1"
                        >
                            <button
                                v-for="item in ['front', 'back', 'qr'] as const"
                                :key="item"
                                type="button"
                                class="fl-focus-glow flex flex-1 items-center justify-center gap-1 rounded-md border px-1 py-1 text-[10px] font-medium transition-colors active:scale-95"
                                :class="
                                    card.checklist[item]
                                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400'
                                        : 'border-white/10 text-white/40 hover:border-white/25'
                                "
                                @click="toggleChecklist(card, item)"
                            >
                                <Check
                                    v-if="card.checklist[item]"
                                    class="size-3"
                                />
                                {{ checklistLabels[item] }}
                            </button>
                        </div>

                        <div
                            v-if="columnMeta[key].next"
                            class="mt-2 flex gap-1.5"
                        >
                            <button
                                type="button"
                                class="fl-focus-glow flex flex-1 items-center justify-center gap-1 rounded-md bg-fl-gold px-2 py-1.5 text-[11px] font-medium text-fl-black transition-transform active:scale-95 disabled:pointer-events-none disabled:opacity-60"
                                :disabled="
                                    pendingIds.has(card.id) ||
                                    (key === 'processing' &&
                                        !checklistComplete(card))
                                "
                                :title="
                                    key === 'processing' &&
                                    !checklistComplete(card)
                                        ? 'Confirma frente, reverso y QR antes de marcar lista'
                                        : undefined
                                "
                                @click="advance(card.id, columnMeta[key].next!)"
                            >
                                <Spinner
                                    v-if="pendingIds.has(card.id)"
                                    class="size-3"
                                />
                                <template v-else>
                                    {{ columnMeta[key].nextLabel }}
                                    <ArrowRight class="size-3" />
                                </template>
                            </button>
                            <button
                                type="button"
                                class="fl-focus-glow flex items-center justify-center rounded-md border border-white/10 px-2 py-1.5 text-white/40 transition-colors hover:border-red-400/30 hover:text-red-400 disabled:pointer-events-none disabled:opacity-60"
                                :disabled="pendingIds.has(card.id)"
                                aria-label="Cancelar"
                                @click="cancel(card.id)"
                            >
                                <Ban class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </TransitionGroup>

                <div
                    v-if="!columns[key]?.length"
                    class="flex flex-col items-center gap-1.5 py-8 text-center"
                >
                    <PackageOpen class="size-5 text-white/15" />
                    <p class="text-xs text-white/20">Sin placas aquí</p>
                </div>
            </div>
        </div>
    </div>
</template>
