<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Reveal from '@/components/motion/Reveal.vue';
import StaggerGroup from '@/components/motion/StaggerGroup.vue';
import CTASection from '@/components/public/CTASection.vue';
import EditorialMoment from '@/components/public/EditorialMoment.vue';
import EventCard from '@/components/public/EventCard.vue';
import FaqSection from '@/components/public/FaqSection.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import HowItWorksSteps from '@/components/public/HowItWorksSteps.vue';
import LegacyLineTrail from '@/components/public/LegacyLineTrail.vue';
import LegacyProfileShowcase from '@/components/public/LegacyProfileShowcase.vue';
import LegacyTimeline from '@/components/public/LegacyTimeline.vue';
import MascotGuide from '@/components/public/MascotGuide.vue';
import LegacyScanMedia from '@/components/public/media/LegacyScanMedia.vue';
import PlateMedia from '@/components/public/media/PlateMedia.vue';
import PlateFlowSwitch from '@/components/public/PlateFlowSwitch.vue';
import PlateMountDemo from '@/components/public/PlateMountDemo.vue';
import ScrollCueButton from '@/components/public/ScrollCueButton.vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import StickyLegacyJourney from '@/components/public/StickyLegacyJourney.vue';
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

const mascotTips = [
    {
        id: 'hero',
        text: 'Desliza hacia abajo: te muestro cómo tu medalla se convierte en tu Legacy.',
    },
    {
        id: 'journey',
        text: 'Mira cómo la medalla se transforma en una placa con su propio código.',
    },
    {
        id: 'legacy-code',
        text: 'Cada placa trae un Legacy Code único: escanéalo y llegas directo a tu historia.',
    },
    {
        id: 'profile',
        text: 'Aquí vive tu colección: medallas, tiempos y ciudades, todo en un solo lugar.',
    },
    {
        id: 'events',
        text: 'Estos son los próximos eventos disponibles. ¡Elige tu siguiente meta!',
    },
    {
        id: 'final',
        text: '¿Listo? Crea tu Legacy ID y empieza tu colección.',
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
        data-mascot-tip="hero"
        title="TU META TERMINA.
TU HISTORIA NO."
        subtitle="Finisher Legacy transforma cada logro deportivo en una historia que puedes conservar, revivir y compartir."
        primary-label="CREAR MI LEGACY"
        :primary-href="register()"
        secondary-label="DESCUBRIR CÓMO FUNCIONA"
        :secondary-href="howItWorks()"
    />

    <!-- El significado -->
    <EditorialMoment />

    <!-- El ecosistema, como secuencia conectada -->
    <section class="pt-20 pb-10 sm:pt-24">
        <div class="legacy-line mx-auto max-w-2xl" aria-hidden="true" />
        <Reveal
            as="div"
            class="mx-auto max-w-2xl px-4 text-center sm:px-6 lg:px-8"
        >
            <SectionHeading
                eyebrow="El concepto"
                title="De la medalla al Legacy"
                description="Cada medalla guarda mucho más que una meta. Sigue el scroll: es la misma historia, transformándose."
            />
        </Reveal>
    </section>
    <StickyLegacyJourney data-mascot-tip="journey" />

    <!-- Legacy Code -->
    <section
        data-mascot-tip="legacy-code"
        class="bg-fl-graphite/30 py-24 sm:py-28"
    >
        <Reveal as="div" class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Legacy Code"
                title="UN CÓDIGO.
TODA UNA HISTORIA."
                description="Cada placa Finisher Legacy incluye un Legacy Code único. Al escanearlo, el Athlete podrá acceder al registro digital asociado con ese logro."
                class="mb-14"
            />
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <PlateMedia />
                <LegacyScanMedia />
            </div>
            <div class="mt-16 flex justify-center">
                <PlateMountDemo />
            </div>
        </Reveal>
        <div class="mt-14 flex justify-center">
            <ScrollCueButton label="Sigue tu Legacy" />
        </div>
    </section>

    <!-- Dos caminos -->
    <section class="py-24 sm:py-28">
        <Reveal as="div" class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Cómo preparamos tu placa"
                title="Dos caminos. El mismo Legacy."
                class="mb-14"
            />
            <PlateFlowSwitch />
        </Reveal>
    </section>

    <!-- Legacy Profile preview -->
    <section data-mascot-tip="profile" class="bg-fl-graphite/30 py-24 sm:py-28">
        <Reveal
            as="div"
            class="mx-auto grid max-w-6xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-2 lg:px-8"
        >
            <SectionHeading
                align="left"
                eyebrow="Legacy Profile"
                title="Una carrera es un recuerdo.
Muchas carreras son un Legacy."
                description="Cada Legacy Profile reúne tus medallas, tus tiempos y las historias detrás de cada meta. Público si tú lo decides, siempre tuyo."
            />
            <LegacyProfileShowcase :profile="legacyProfile" />
        </Reveal>
    </section>

    <!-- Timeline -->
    <section class="py-24 sm:py-28">
        <Reveal as="div" class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Tu historia crece"
                title="Tu Legacy crece contigo"
                description="Cada carrera que vinculas se suma a tu Legacy Profile. Así se ve con el tiempo."
                class="mb-16"
            />
            <LegacyTimeline />
        </Reveal>
    </section>

    <!-- Cómo funciona -->
    <section class="py-24 sm:py-28">
        <Reveal as="div" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Cómo funciona"
                title="Corre. Registra. Preserva. Comparte."
                class="mb-14"
            />
            <HowItWorksSteps :steps="steps" />
        </Reveal>
    </section>

    <!-- Eventos destacados -->
    <section
        v-if="featuredEditions.length"
        data-mascot-tip="events"
        class="bg-fl-graphite/30 py-24 sm:py-28"
    >
        <Reveal as="div" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Eventos"
                title="Próximos eventos"
                class="mb-14"
            />
            <StaggerGroup
                as="div"
                class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <EventCard
                    v-for="edition in featuredEditions"
                    :key="edition.id"
                    :edition="edition"
                />
            </StaggerGroup>
            <div class="mt-12 flex justify-center">
                <Link
                    :href="eventsIndex()"
                    class="text-sm font-semibold tracking-wide text-fl-gold-soft hover:text-fl-gold"
                >
                    EXPLORAR EVENTOS →
                </Link>
            </div>
        </Reveal>
    </section>

    <!-- Preguntas frecuentes -->
    <section class="py-24 sm:py-28">
        <Reveal as="div" class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                eyebrow="Preguntas frecuentes"
                title="Dudas antes de empezar"
                class="mb-14"
            />
            <FaqSection />
        </Reveal>
    </section>

    <CTASection
        data-mascot-tip="final"
        title="Terminaste la carrera. Ahora conserva lo que significó."
        description="Crea tu Legacy ID y empieza a construir tu colección de logros."
        primary-label="CREAR MI LEGACY"
        :primary-href="register()"
        secondary-label="EXPLORAR EVENTOS"
        :secondary-href="eventsIndex()"
        show-mascot
        cinematic
    />

    <!-- Mounted last on purpose: it's `position: absolute` (sized against
         <main> in PublicLayout.vue) so DOM order doesn't affect its
         layout, only its paint order — being last means it paints above
         every other section's own `position: relative` wrapper instead of
         getting buried behind the Hero. -->
    <LegacyLineTrail />

    <MascotGuide
        welcome="¡Hola! 👋 Soy la mascota de Finisher Legacy, tu guía por aquí."
        :tips="mascotTips"
    />
</template>
