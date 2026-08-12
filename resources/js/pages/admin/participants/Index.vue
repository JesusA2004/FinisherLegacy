<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminTable from '@/components/admin/AdminTable.vue';

defineProps<{
    participants: {
        data: {
            id: number;
            name: string;
            bib_number: string;
            event: string | null;
            race: string | null;
            has_result: string;
            source: string;
        }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string };
}>();

const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'bib_number', label: 'Número' },
    { key: 'event', label: 'Evento' },
    { key: 'race', label: 'Distancia' },
    { key: 'has_result', label: 'Con resultado' },
    { key: 'source', label: 'Origen' },
];
</script>

<template>
    <Head title="Participantes" />

    <div class="p-4 md:p-8">
        <h1 class="mb-6 text-xl font-bold text-white">Participantes</h1>

        <AdminTable
            :columns="columns"
            :rows="participants"
            searchable
            :initial-query="filters.q"
        />
    </div>
</template>
