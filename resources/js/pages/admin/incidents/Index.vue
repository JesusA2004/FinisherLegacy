<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    incidents: {
        data: {
            id: number;
            event: string | null;
            type: string;
            description: string;
            status: string;
            reported_by: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string };
}>();

const columns = [
    { key: 'event', label: 'Evento' },
    { key: 'type', label: 'Tipo' },
    { key: 'description', label: 'Descripción' },
    { key: 'status', label: 'Estado' },
    { key: 'reported_by', label: 'Reportado por' },
    { key: 'actions', label: '' },
];

function resolve(id: number) {
    router.patch(`/admin/incidents/${id}/resolve`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Incidencias" />

    <div class="p-4 md:p-8">
        <h1 class="mb-6 text-xl font-bold text-white">Incidencias</h1>

        <AdminTable :columns="columns" :rows="incidents" searchable :initial-query="filters.q">
            <template #cell-status="{ row }">
                <Badge
                    variant="outline"
                    :class="
                        row.status === 'open'
                            ? 'border-red-500/30 text-red-400'
                            : row.status === 'in_progress'
                              ? 'border-amber-500/30 text-amber-400'
                              : 'border-emerald-500/30 text-emerald-400'
                    "
                >
                    {{ row.status }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <Button
                    v-if="row.status !== 'resolved'"
                    size="sm"
                    variant="outline"
                    class="border-white/15 text-white hover:bg-white/10 hover:text-white"
                    @click="resolve(row.id as number)"
                >
                    Resolver
                </Button>
            </template>
        </AdminTable>
    </div>
</template>
