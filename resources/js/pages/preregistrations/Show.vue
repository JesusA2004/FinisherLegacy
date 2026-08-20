<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, MapPin } from '@lucide/vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import MascotSpotlight from '@/components/public/MascotSpotlight.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { show as eventShow } from '@/routes/events';

const { token, status, firstName, bibNumber, event, edition, race, qrUrl } =
    defineProps<{
        token: string;
        status: string;
        firstName: string;
        lastName: string;
        bibNumber: string | null;
        event: { name: string; slug: string };
        edition: { name: string; event_date: string };
        race: string;
        qrUrl: string;
    }>();

const formattedDate = new Date(
    `${edition.event_date}T00:00:00`,
).toLocaleDateString('es-MX', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const statusCopy: Record<string, string> = {
    pending: 'En espera de confirmación oficial del evento.',
    matched: 'Encontramos tu resultado — ya casi es parte de tu Legacy.',
    confirmed: 'Tu lugar está confirmado.',
    completed: 'Este prerregistro ya se convirtió en tu Legacy.',
    cancelled: 'Este prerregistro fue cancelado.',
};
</script>

<template>
    <Head :title="`Prerregistro confirmado — ${event.name}`" />

    <section class="mx-auto max-w-lg px-4 py-16 sm:px-6 lg:px-8">
        <div class="mb-8 flex justify-center">
            <FinisherLegacyLogo variant="mark" size="md" />
        </div>

        <MascotSpotlight
            title="Todo listo para tu evento."
            :description="statusCopy[status] ?? statusCopy.pending"
        />

        <div
            class="mt-6 rounded-2xl border-2 border-legacy-copper/30 bg-gradient-to-b from-legacy-copper/10 to-legacy-carbon/40 p-6 text-center"
        >
            <p class="text-lg font-bold text-legacy-bone">{{ firstName }}</p>
            <p class="mt-1 text-sm text-legacy-titanium">{{ event.name }}</p>
            <p class="text-sm text-legacy-copper-soft">{{ edition.name }} · {{ race }}</p>

            <div
                class="mt-4 flex flex-wrap items-center justify-center gap-3 text-xs text-legacy-titanium/60"
            >
                <span class="inline-flex items-center gap-1">
                    <Calendar class="size-3.5" />
                    <span class="capitalize">{{ formattedDate }}</span>
                </span>
                <span v-if="bibNumber" class="inline-flex items-center gap-1">
                    <MapPin class="size-3.5" />
                    Número {{ bibNumber }}
                </span>
            </div>

            <div class="mx-auto mt-6 w-40 rounded-xl bg-white p-2">
                <img
                    :src="qrUrl"
                    alt="Código QR de tu prerregistro"
                    class="size-full"
                />
            </div>

            <p
                class="mt-4 rounded-md border border-legacy-titanium/10 bg-legacy-ink px-3 py-1.5 font-mono text-xs text-legacy-titanium/60"
            >
                {{ token }}
            </p>
        </div>

        <Button
            as-child
            variant="outline"
            class="mt-6 w-full border-legacy-titanium/25 bg-transparent text-legacy-bone hover:bg-white/10 hover:text-legacy-bone"
        >
            <Link :href="eventShow(event.slug)">Volver al evento</Link>
        </Button>
        <Button as-child variant="ghost" class="mt-2 w-full text-legacy-titanium/60">
            <Link :href="home()">Ir al inicio</Link>
        </Button>
    </section>
</template>
