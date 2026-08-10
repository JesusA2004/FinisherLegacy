<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Badge } from '@/components/ui/badge';

defineProps<{
    editions: {
        data: {
            id: number;
            event: string;
            edition: string;
            event_date: string;
            status: string;
            phase: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string };
}>();

const columns = [
    { key: 'event', label: 'Evento' },
    { key: 'edition', label: 'Edición' },
    { key: 'event_date', label: 'Fecha' },
    { key: 'phase', label: 'Fase' },
];
</script>

<template>
    <Head title="Eventos y ediciones" />

    <div class="p-4 md:p-8">
        <h1 class="mb-6 text-xl font-bold text-white">Eventos y ediciones</h1>

        <AdminTable :columns="columns" :rows="editions" searchable :initial-query="filters.q">
            <template #cell-phase="{ row }">
                <Badge variant="outline" class="border-white/15 text-white/60">
                    {{ row.phase }}
                </Badge>
            </template>
        </AdminTable>
    </div>
</template>
