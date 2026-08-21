<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Mail, MessageCircle } from '@lucide/vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import { useCanonicalUrl } from '@/composables/useCanonicalUrl';
import { howItWorks } from '@/routes';

const SUPPORT_EMAIL = 'hola@finisherlegacy.com';
const canonicalUrl = useCanonicalUrl();
</script>

<template>
    <Head title="Contacto">
        <meta
            name="description"
            content="¿Dudas sobre tu Legacy, un evento o una placa? Ponte en contacto con el equipo de Finisher Legacy."
        />
        <link v-if="canonicalUrl" rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" content="Contacto | Finisher Legacy" />
        <meta
            property="og:description"
            content="¿Dudas sobre tu Legacy, un evento o una placa? Escríbenos."
        />
        <meta v-if="canonicalUrl" property="og:url" :content="canonicalUrl" />
    </Head>

    <section class="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6 lg:px-8">
        <SectionHeading
            eyebrow="Contacto"
            title="Hablemos"
            description="¿Tienes dudas sobre tu Legacy, un evento o una placa? Escríbenos."
        />

        <div class="mt-12 grid gap-4 sm:grid-cols-2">
            <a
                :href="`mailto:${SUPPORT_EMAIL}`"
                class="flex flex-col items-center gap-3 rounded-2xl border border-white/10 bg-fl-graphite/50 p-8 transition-colors hover:border-fl-gold/40"
            >
                <Mail class="size-6 text-fl-gold-soft" />
                <span class="font-medium text-white">Correo</span>
                <span class="text-sm text-white/70">{{ SUPPORT_EMAIL }}</span>
            </a>

            <Link
                :href="howItWorks()"
                class="flex flex-col items-center gap-3 rounded-2xl border border-white/10 bg-fl-graphite/50 p-8 transition-colors hover:border-fl-gold/40"
            >
                <MessageCircle class="size-6 text-fl-gold-soft" />
                <span class="font-medium text-white">¿Cómo funciona?</span>
                <span class="text-sm text-white/70"
                    >Resuelve dudas comunes aquí</span
                >
            </Link>
        </div>

        <p class="mt-12 text-sm text-white/50">
            Un centro de soporte completo estará disponible próximamente.
        </p>
    </section>
</template>
