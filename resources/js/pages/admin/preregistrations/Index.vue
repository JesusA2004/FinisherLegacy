<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Badge } from '@/components/ui/badge';
import {
    preregistrationStatus,
    statusClass,
    statusLabel,
} from '@/lib/statusLabels';

defineProps<{
    preregistrations: {
        data: {
            id: number;
            name: string;
            email: string;
            event: string | null;
            race: string | null;
            bib_number: string | null;
            status: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string };
}>();

const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Correo' },
    { key: 'event', label: 'Evento' },
    { key: 'race', label: 'Distancia' },
    { key: 'bib_number', label: 'Número' },
    { key: 'status', label: 'Estado' },
];
</script>

<template>
    <Head title="Prerregistros" />

    <div class="p-4 md:p-8">
        <h1 class="mb-6 text-xl font-bold text-white">Prerregistros</h1>

        <AdminTable
            :columns="columns"
            :rows="preregistrations"
            searchable
            :initial-query="filters.q"
        >
            <template #cell-status="{ row }">
                <Badge
                    variant="outline"
                    :class="
                        statusClass(preregistrationStatus, row.status as string)
                    "
                >
                    {{
                        statusLabel(preregistrationStatus, row.status as string)
                    }}
                </Badge>
            </template>
        </AdminTable>
    </div>
</template>
