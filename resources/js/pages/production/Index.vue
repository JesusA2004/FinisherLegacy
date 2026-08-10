<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight, Ban } from '@lucide/vue';
import { updateStatus } from '@/actions/App/Http/Controllers/ProductionController';

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
};

const { columns } = defineProps<{
    columns: Record<'pending' | 'processing' | 'ready' | 'delivered' | 'issue', Card[]>;
}>();

const columnMeta: Record<string, { title: string; next: string | null; nextLabel: string }> = {
    pending: { title: 'Pendiente', next: 'processing', nextLabel: 'Iniciar producción' },
    processing: { title: 'En proceso', next: 'ready', nextLabel: 'Marcar lista' },
    ready: { title: 'Lista', next: 'delivered', nextLabel: 'Marcar entregada' },
    delivered: { title: 'Entregada', next: null, nextLabel: '' },
    issue: { title: 'Incidencia', next: null, nextLabel: '' },
};

function advance(plateId: number, next: string) {
    router.patch(updateStatus(plateId).url, { status: next }, { preserveScroll: true });
}

function cancel(plateId: number) {
    router.patch(updateStatus(plateId).url, { status: 'cancelled' }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Producción" />

    <div class="min-h-svh p-4 md:p-6">
        <h1 class="mb-6 text-xl font-bold text-white">Producción</h1>

        <div class="grid grid-cols-1 gap-4 overflow-x-auto sm:grid-cols-2 lg:grid-cols-5">
            <div
                v-for="key in ['pending', 'processing', 'ready', 'delivered', 'issue'] as const"
                :key="key"
                class="rounded-xl border border-white/10 bg-fl-graphite/30 p-3"
            >
                <p class="mb-3 text-xs font-semibold tracking-wide text-white/50 uppercase">
                    {{ columnMeta[key].title }}
                    <span class="text-white/30">({{ columns[key]?.length ?? 0 }})</span>
                </p>

                <div class="space-y-2">
                    <div
                        v-for="card in columns[key]"
                        :key="card.id"
                        class="rounded-lg border border-white/10 bg-fl-black/60 p-3"
                    >
                        <p class="truncate text-sm font-medium text-white">
                            {{ card.athlete_name }}
                        </p>
                        <p class="truncate text-xs text-white/40">
                            {{
                                [card.bib_number ? `#${card.bib_number}` : null, card.event_name]
                                    .filter(Boolean)
                                    .join(' · ')
                            }}
                        </p>
                        <p class="mt-1 font-mono text-[10px] text-white/30">
                            {{ card.serial_number }}
                        </p>

                        <div v-if="columnMeta[key].next" class="mt-2 flex gap-1.5">
                            <button
                                type="button"
                                class="fl-focus-glow flex flex-1 items-center justify-center gap-1 rounded-md bg-fl-gold px-2 py-1.5 text-[11px] font-medium text-fl-black transition-transform active:scale-95"
                                @click="advance(card.id, columnMeta[key].next!)"
                            >
                                {{ columnMeta[key].nextLabel }}
                                <ArrowRight class="size-3" />
                            </button>
                            <button
                                type="button"
                                class="fl-focus-glow flex items-center justify-center rounded-md border border-white/10 px-2 py-1.5 text-white/40 hover:text-red-400"
                                aria-label="Cancelar"
                                @click="cancel(card.id)"
                            >
                                <Ban class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="!columns[key]?.length"
                        class="py-6 text-center text-xs text-white/20"
                    >
                        Sin placas aquí
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
