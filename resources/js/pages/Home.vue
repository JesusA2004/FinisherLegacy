<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Reveal from '@/components/motion/Reveal.vue';
import StaggerGroup from '@/components/motion/StaggerGroup.vue';
import CTASection from '@/components/public/CTASection.vue';
import EventCard from '@/components/public/EventCard.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import HowItWorksSteps from '@/components/public/HowItWorksSteps.vue';
import LegacyCodePreview from '@/components/public/LegacyCodePreview.vue';
import LegacyProfilePreview from '@/components/public/LegacyProfilePreview.vue';
import MascotSpotlight from '@/components/public/MascotSpotlight.vue';
import PlateFlowCard from '@/components/public/PlateFlowCard.vue';
import PlateShowcase from '@/components/public/PlateShowcase.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import { howItWorks, register } from '@/routes';
import { index as eventsIndex } from '@/routes/events';
import type {
    EventEditionCard,
    LegacyProfilePreview as LegacyProfileType,
} from '@/types';

defineProps<{
    featuredEditions: EventEditionCard[];
    legacyProfile: LegacyProfileType | null;
}>();

const steps = [
    {
        number: '01',
        title: 'Corre',
        description: 'Participa en tu evento.',
    },
    {
        number: '02',
        title: 'Registra',
        description: 'Encuentra o registra tu resultado.',
    },
    {
        number: '03',
        title: 'Preserva',
        description:
            'Tu placa y Legacy Code conectan el logro con tu Legacy Profile.',
    },
    {
        number: '04',
        title: 'Comparte',
        description:
            'Tu historia queda disponible para volver a vivirla y compartirla.',
    },
];
</script>

<template>
    <Head title="Finisher Legacy — Tu meta termina. Tu historia no.">
        <meta
            name="description"
            content="Finisher Legacy transforma cada logro deportivo en una historia que puedes conservar, revivir y compartir."
        />
        <meta property="og:title" content="Finisher Legacy" />
        <meta
            property="og:description"
            content="Finisher Legacy transforma cada logro deportivo en una historia que puedes conservar, revivir y compartir."
        />
        <meta property="og:type" content="website" />
    </Head>

    <HeroSection
        title="TU META TERMINA.
TU HISTORIA NO."
        subtitle="Finisher Legacy transforma cada logro deportivo en una historia que puedes conservar, revivir y compartir."
        primary-label="CREAR MI LEGACY"
        :primary-href="register()"
        secondary-label="DESCUBRIR CÓMO FUNCIONA"
        :secondary-href="howItWorks()"
    />

    <!-- Concepto -->
    <section class="border-t border-white/5 py-24">
        <Reveal as="div" class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="El concepto"
                title="MEDALLA → PLACA → LEGACY CODE → LEGACY PROFILE"
                description="Cada medalla guarda mucho más que una meta. Guarda tiempo, esfuerzo, personas, lugares y una historia. Finisher Legacy conecta ese recuerdo físico con su historia digital."
            />
        </Reveal>
    </section>

    <!-- Cómo funciona -->
    <section class="border-t border-white/5 bg-fl-graphite/20 py-24">
        <Reveal as="div" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Cómo funciona"
                title="Corre. Registra. Preserva. Comparte."
                class="mb-14"
            />
            <HowItWorksSteps :steps="steps" />
        </Reveal>
    </section>

    <!-- Legacy Code -->
    <section class="border-t border-white/5 py-24">
        <Reveal as="div" class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Legacy Code"
                title="UN CÓDIGO.
TODA UNA HISTORIA."
                description="Cada placa Finisher Legacy incluye un Legacy Code único. Al escanearlo, el Athlete podrá acceder al registro digital asociado con ese logro."
                class="mb-14"
            />
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <PlateShowcase />
                <LegacyCodePreview />
            </div>
        </Reveal>
    </section>

    <!-- Dos caminos -->
    <section class="border-t border-white/5 bg-fl-graphite/20 py-24">
        <Reveal as="div" class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Cómo preparamos tu placa"
                title="Dos caminos. El mismo Legacy."
                class="mb-14"
            />
            <StaggerGroup as="div" class="grid gap-6 md:grid-cols-2">
                <PlateFlowCard
                    eyebrow="Camino automático"
                    title="Cuando trabajamos conectados con el evento"
                    :chain="['Corredor', 'Resultado', 'Placa', 'Legacy']"
                    description="Si contamos con los datos del evento, podemos preparar tu placa utilizando la información oficial disponible y vincularla con tu Legacy."
                    highlighted
                />
                <PlateFlowCard
                    eyebrow="Camino flexible"
                    title="Cuando no existe integración"
                    :chain="[
                        'Placa',
                        'Legacy Code',
                        'Escanea después',
                        'Vincula a tu Legacy',
                    ]"
                    description="Si el evento no comparte sus datos, tu placa puede entregarse igualmente. Después podrás escanear su Legacy Code y vincularla desde casa."
                />
            </StaggerGroup>
        </Reveal>
    </section>

    <!-- Legacy Profile preview -->
    <section class="border-t border-white/5 py-24">
        <Reveal
            as="div"
            class="mx-auto grid max-w-6xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8"
        >
            <SectionHeading
                align="left"
                eyebrow="Legacy Profile"
                title="Tu colección de logros, siempre contigo."
                description="Cada Legacy Profile reúne tus medallas, tus tiempos y las historias detrás de cada meta. Público si tú lo decides, siempre tuyo."
            />
            <LegacyProfilePreview :profile="legacyProfile" />
        </Reveal>
    </section>

    <!-- Eventos destacados -->
    <section
        v-if="featuredEditions.length"
        class="border-t border-white/5 bg-fl-graphite/20 py-24"
    >
        <Reveal as="div" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Eventos"
                title="Próximos eventos"
                class="mb-14"
            />
            <StaggerGroup as="div" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <EventCard
                    v-for="edition in featuredEditions"
                    :key="edition.id"
                    :edition="edition"
                />
            </StaggerGroup>
            <div class="mt-12 flex justify-center">
                <Link
                    :href="eventsIndex()"
                    class="text-sm font-semibold tracking-wide text-fl-gold hover:text-fl-gold-soft"
                >
                    EXPLORAR EVENTOS →
                </Link>
            </div>
        </Reveal>
    </section>

    <section class="border-t border-white/5 bg-fl-graphite/20 py-20">
        <Reveal as="div" class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <MascotSpotlight
                title="No colecciones objetos. Preserva momentos."
                description="Muy pronto conocerás a la mascota Finisher Legacy en eventos y en tu propio Legacy Profile."
            />
        </Reveal>
    </section>

    <CTASection
        title="Tu historia merece quedarse."
        description="Crea tu Legacy ID y empieza a construir tu colección de logros."
        primary-label="CREAR MI LEGACY"
        :primary-href="register()"
    />
</template>
