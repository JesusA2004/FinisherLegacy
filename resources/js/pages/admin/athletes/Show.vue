<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Award, Boxes, Trophy, UserCircle } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

type Athlete = {
    id: number;
    full_name: string;
    email: string | null;
    phone: string | null;
    birth_date: string | null;
    country: string | null;
    identity_status: string;
    user: { id: number; name: string; email: string } | null;
};

type Participation = {
    id: number;
    event: string | null;
    edition: string | null;
    race: string | null;
    bib_number: string;
    official_time: string | null;
    source: string;
};

type PlateRow = {
    id: number;
    serial_number: string;
    event: string | null;
    legacy_code: string | null;
    status: string;
};

type MedalRow = {
    id: number;
    title: string;
    event_date: string | null;
    distance_label: string | null;
};

defineProps<{
    athlete: Athlete;
    participations: Participation[];
    plates: PlateRow[];
    medals: MedalRow[];
}>();
</script>

<template>
    <Head :title="athlete.full_name" />

    <div class="p-4 md:p-8">
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h1
                    class="flex items-center gap-2 text-xl font-bold text-white"
                >
                    <UserCircle class="size-6 text-fl-gold" />
                    {{ athlete.full_name }}
                </h1>
                <p class="mt-1 text-sm text-white/50">
                    {{
                        athlete.user
                            ? `Cuenta vinculada: ${athlete.user.email}`
                            : 'Sin cuenta vinculada'
                    }}
                </p>
            </div>
            <Badge
                variant="outline"
                class="border-emerald-500/30 text-emerald-400"
            >
                {{ participations.length }} participacion{{
                    participations.length === 1 ? '' : 'es'
                }}
            </Badge>
        </div>

        <div
            class="mb-6 grid grid-cols-2 gap-3 rounded-xl border border-white/10 bg-fl-graphite/30 p-4 text-sm md:grid-cols-4"
        >
            <div>
                <p class="text-xs text-white/30 uppercase">Email</p>
                <p class="text-white">{{ athlete.email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-white/30 uppercase">Teléfono</p>
                <p class="text-white">{{ athlete.phone ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-white/30 uppercase">
                    Fecha de nacimiento
                </p>
                <p class="text-white">{{ athlete.birth_date ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-white/30 uppercase">País</p>
                <p class="text-white">{{ athlete.country ?? '—' }}</p>
            </div>
        </div>

        <section class="mb-6">
            <h2
                class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-white/70"
            >
                <Trophy class="size-4" /> Participaciones por evento
            </h2>
            <div class="overflow-x-auto rounded-xl border border-white/10">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-white/10 bg-fl-graphite/40 text-left text-xs text-white/50 uppercase"
                        >
                            <th class="px-4 py-3 font-medium">Evento</th>
                            <th class="px-4 py-3 font-medium">Edición</th>
                            <th class="px-4 py-3 font-medium">Carrera</th>
                            <th class="px-4 py-3 font-medium">Dorsal</th>
                            <th class="px-4 py-3 font-medium">Tiempo</th>
                            <th class="px-4 py-3 font-medium">Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in participations"
                            :key="p.id"
                            class="border-b border-white/5 text-white/80 last:border-0"
                        >
                            <td class="px-4 py-3">{{ p.event ?? '—' }}</td>
                            <td class="px-4 py-3">{{ p.edition ?? '—' }}</td>
                            <td class="px-4 py-3">{{ p.race ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-fl-gold">
                                #{{ p.bib_number }}
                            </td>
                            <td class="px-4 py-3">
                                {{ p.official_time ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-white/40">
                                {{ p.source }}
                            </td>
                        </tr>
                        <tr v-if="!participations.length">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-white/30"
                            >
                                Sin participaciones todavía.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-6 md:grid-cols-2">
            <section>
                <h2
                    class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-white/70"
                >
                    <Boxes class="size-4" /> Placas
                </h2>
                <div class="space-y-2">
                    <div
                        v-for="plate in plates"
                        :key="plate.id"
                        class="rounded-lg border border-white/10 bg-fl-black/40 p-3 text-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-white/70">{{
                                plate.serial_number
                            }}</span>
                            <Badge
                                variant="outline"
                                class="border-white/20 text-white/50"
                                >{{ plate.status }}</Badge
                            >
                        </div>
                        <p class="mt-1 text-xs text-white/40">
                            {{ plate.event ?? '—' }} ·
                            <Link
                                v-if="plate.legacy_code"
                                :href="`/l/${plate.legacy_code}`"
                                class="text-fl-gold hover:underline"
                            >
                                {{ plate.legacy_code }}
                            </Link>
                        </p>
                    </div>
                    <p v-if="!plates.length" class="text-sm text-white/30">
                        Sin placas.
                    </p>
                </div>
            </section>

            <section>
                <h2
                    class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-white/70"
                >
                    <Award class="size-4" /> Medallas
                </h2>
                <div class="space-y-2">
                    <div
                        v-for="medal in medals"
                        :key="medal.id"
                        class="rounded-lg border border-white/10 bg-fl-black/40 p-3 text-sm"
                    >
                        <p class="text-white/80">{{ medal.title }}</p>
                        <p class="text-xs text-white/40">
                            {{ medal.distance_label ?? '—' }} ·
                            {{ medal.event_date ?? '—' }}
                        </p>
                    </div>
                    <p v-if="!medals.length" class="text-sm text-white/30">
                        Sin medallas.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
