<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Mail, MessageCircle } from '@lucide/vue';
import SectionHeading from '@/components/public/SectionHeading.vue';
import { howItWorks } from '@/routes';

const SUPPORT_EMAIL = 'hola@finisherlegacy.com';
</script>

<template>
    <Head title="Contacto">
        <meta
            name="description"
            content="Ponte en contacto con el equipo de Finisher Legacy."
        />
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
                class="flex flex-col items-center gap-3 rounded-2xl border border-legacy-titanium/10 bg-legacy-carbon/50 p-8 transition-colors hover:border-legacy-copper/40"
            >
                <Mail class="size-6 text-legacy-copper-soft" />
                <span class="font-medium text-legacy-bone">Correo</span>
                <span class="text-sm text-legacy-titanium/70">{{ SUPPORT_EMAIL }}</span>
            </a>

            <Link
                :href="howItWorks()"
                class="flex flex-col items-center gap-3 rounded-2xl border border-legacy-titanium/10 bg-legacy-carbon/50 p-8 transition-colors hover:border-legacy-copper/40"
            >
                <MessageCircle class="size-6 text-legacy-copper-soft" />
                <span class="font-medium text-legacy-bone">¿Cómo funciona?</span>
                <span class="text-sm text-legacy-titanium/70"
                    >Resuelve dudas comunes aquí</span
                >
            </Link>
        </div>

        <p class="mt-12 text-sm text-legacy-titanium/50">
            Un centro de soporte completo estará disponible próximamente.
        </p>
    </section>
</template>
