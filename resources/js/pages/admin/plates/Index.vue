<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Badge } from '@/components/ui/badge';

defineProps<{
    plates: {
        data: {
            id: number;
            serial_number: string;
            athlete_name: string;
            event: string | null;
            status: string;
            generation_mode: string;
            legacy_code: string | null;
            owner: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string };
}>();

const columns = [
    { key: 'serial_number', label: 'Serie' },
    { key: 'athlete_name', label: 'Atleta' },
    { key: 'event', label: 'Evento' },
    { key: 'generation_mode', label: 'Modo' },
    { key: 'status', label: 'Estado' },
    { key: 'legacy_code', label: 'Legacy Code' },
    { key: 'owner', label: 'Propietario' },
];
</script>

<template>
    <Head title="Placas" />

    <div class="p-4 md:p-8">
        <h1 class="mb-6 text-xl font-bold text-white">Placas</h1>

        <AdminTable :columns="columns" :rows="plates" searchable :initial-query="filters.q">
            <template #cell-status="{ row }">
                <Badge variant="outline" class="border-white/15 text-white/60">
                    {{ row.status }}
                </Badge>
            </template>
        </AdminTable>
    </div>
</template>
