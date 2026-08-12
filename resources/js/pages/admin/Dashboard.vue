<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Boxes,
    Calendar,
    ClipboardList,
    PackageCheck,
    TriangleAlert,
    Users,
} from '@lucide/vue';
import { plateStatus, statusLabel } from '@/lib/statusLabels';

const { stats } = defineProps<{
    stats: {
        athletes: number;
        events: number;
        participants: number;
        preregistrations: number;
        plates_today: number;
        pending_production: number;
        delivered: number;
        open_incidents: number;
    };
    productionByStatus: Record<string, number>;
}>();

const cards = [
    { label: 'Atletas', value: stats.athletes, icon: Users },
    { label: 'Eventos', value: stats.events, icon: Calendar },
    { label: 'Participantes', value: stats.participants, icon: Users },
    {
        label: 'Prerregistros',
        value: stats.preregistrations,
        icon: ClipboardList,
    },
    { label: 'Placas hoy', value: stats.plates_today, icon: Boxes },
    {
        label: 'Pendientes de producción',
        value: stats.pending_production,
        icon: Boxes,
    },
    { label: 'Entregadas', value: stats.delivered, icon: PackageCheck },
    {
        label: 'Incidencias abiertas',
        value: stats.open_incidents,
        icon: TriangleAlert,
    },
];
</script>

<template>
    <Head title="Panel de administración" />

    <div class="p-4 md:p-8">
        <h1 class="mb-6 text-xl font-bold text-white">Resumen</h1>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.label"
                class="rounded-xl border border-white/10 bg-fl-graphite/40 p-5"
            >
                <component :is="card.icon" class="size-5 text-fl-gold" />
                <p class="mt-3 text-2xl font-bold text-white">
                    {{ card.value }}
                </p>
                <p class="text-sm text-white/50">{{ card.label }}</p>
            </div>
        </div>

        <div
            class="mt-8 rounded-xl border border-white/10 bg-fl-graphite/40 p-5"
        >
            <h2 class="mb-3 text-sm font-semibold text-white/60 uppercase">
                Placas por estado
            </h2>
            <div class="flex flex-wrap gap-3">
                <div
                    v-for="(count, status) in productionByStatus"
                    :key="status"
                    class="rounded-lg border border-white/10 px-4 py-2 text-sm"
                >
                    <span class="text-white/50">{{
                        statusLabel(plateStatus, String(status))
                    }}</span>
                    <span class="ml-2 font-semibold text-white">{{
                        count
                    }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
