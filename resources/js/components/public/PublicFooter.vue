<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import { contact, home, howItWorks, privacy, terms } from '@/routes';
import { index as eventsIndex } from '@/routes/events';

const year = new Date().getFullYear();

const product = [
    { label: 'Inicio', href: home() },
    { label: 'Cómo funciona', href: howItWorks() },
    { label: 'Eventos', href: eventsIndex() },
];

const legal = [
    { label: 'Privacidad', href: privacy() },
    { label: 'Términos', href: terms() },
];
</script>

<template>
    <footer class="border-t border-white/10 bg-fl-black">
        <div
            class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8"
        >
            <div class="md:col-span-2">
                <FinisherLegacyLogo variant="mark" size="lg" />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">
                    Finisher Legacy transforma cada logro deportivo en una
                    historia que puedes conservar, revivir y compartir.
                </p>
            </div>

            <div>
                <h3
                    class="text-xs font-semibold tracking-[0.2em] text-white/40 uppercase"
                >
                    Producto
                </h3>
                <ul class="mt-4 space-y-3">
                    <li v-for="item in product" :key="item.label">
                        <Link
                            :href="item.href"
                            class="text-sm text-white/70 transition-colors hover:text-fl-gold"
                        >
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </div>

            <div>
                <h3
                    class="text-xs font-semibold tracking-[0.2em] text-white/40 uppercase"
                >
                    Soporte
                </h3>
                <ul class="mt-4 space-y-3">
                    <li>
                        <Link
                            :href="contact()"
                            class="text-sm text-white/70 transition-colors hover:text-fl-gold"
                        >
                            Contacto
                        </Link>
                    </li>
                    <li v-for="item in legal" :key="item.label">
                        <Link
                            :href="item.href"
                            class="text-sm text-white/70 transition-colors hover:text-fl-gold"
                        >
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-6 sm:flex-row sm:px-6 lg:px-8"
            >
                <p class="text-xs text-white/40">
                    © {{ year }} Finisher Legacy. Todos los derechos reservados.
                </p>

                <p class="text-xs text-white/25">
                    Próximamente en redes sociales
                </p>
            </div>
        </div>
    </footer>
</template>
