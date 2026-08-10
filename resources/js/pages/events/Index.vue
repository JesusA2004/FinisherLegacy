<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { reactive } from 'vue';
import StaggerGroup from '@/components/motion/StaggerGroup.vue';
import EventCard from '@/components/public/EventCard.vue';
import Pagination from '@/components/public/Pagination.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as eventsIndex } from '@/routes/events';
import type { EventEditionCard } from '@/types';

type Paginated<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const { editions, sports, filters } = defineProps<{
    editions: Paginated<EventEditionCard>;
    sports: Array<{ id: number; name: string; slug: string }>;
    filters: { q: string; sport: string | null; status: string | null };
}>();

const form = reactive({
    q: filters.q ?? '',
    sport: filters.sport ?? 'all',
    status: filters.status ?? 'available',
});

function applyFilters() {
    router.get(
        eventsIndex().url,
        {
            q: form.q || undefined,
            sport: form.sport === 'all' ? undefined : form.sport,
            status: form.status === 'available' ? undefined : form.status,
        },
        { preserveState: true, replace: true },
    );
}

const debouncedApply = useDebounceFn(applyFilters, 350);

const statusOptions = [
    { value: 'available', label: 'Disponibles' },
    { value: 'upcoming', label: 'Próximo' },
    { value: 'ongoing', label: 'En curso' },
    { value: 'finished', label: 'Finalizado' },
];
</script>

<template>
    <Head title="Eventos">
        <meta
            name="description"
            content="Descubre próximos eventos deportivos y su información oficial en Finisher Legacy."
        />
    </Head>

    <section class="border-b border-white/5 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                align="left"
                eyebrow="Eventos"
                title="Explora eventos"
                description="Encuentra tu próxima meta y todo lo que necesitas saber antes de correrla."
            />

            <div class="mt-10 grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-white/40"
                    />
                    <Input
                        v-model="form.q"
                        placeholder="Buscar por evento o ciudad…"
                        class="border-white/10 bg-fl-graphite/60 pl-9 text-white placeholder:text-white/40"
                        @input="debouncedApply"
                    />
                </div>

                <Select
                    :model-value="form.sport"
                    @update:model-value="
                        (value) => {
                            form.sport = String(value);
                            applyFilters();
                        }
                    "
                >
                    <SelectTrigger
                        class="w-full border-white/10 bg-fl-graphite/60 text-white sm:w-44"
                    >
                        <SelectValue placeholder="Deporte" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos los deportes</SelectItem>
                        <SelectItem
                            v-for="sport in sports"
                            :key="sport.id"
                            :value="sport.slug"
                        >
                            {{ sport.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="form.status"
                    @update:model-value="
                        (value) => {
                            form.status = String(value);
                            applyFilters();
                        }
                    "
                >
                    <SelectTrigger
                        class="w-full border-white/10 bg-fl-graphite/60 text-white sm:w-44"
                    >
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in statusOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div
                v-if="editions.data.length === 0"
                class="rounded-2xl border border-white/10 bg-fl-graphite/40 py-20 text-center"
            >
                <p class="text-white/50">
                    No encontramos eventos con esos filtros.
                </p>
            </div>

            <StaggerGroup
                v-else
                as="div"
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <EventCard
                    v-for="edition in editions.data"
                    :key="edition.id"
                    :edition="edition"
                />
            </StaggerGroup>

            <div class="mt-14">
                <Pagination :links="editions.links" />
            </div>
        </div>
    </section>
</template>
