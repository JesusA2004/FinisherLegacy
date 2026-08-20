<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard, home, howItWorks, login, register } from '@/routes';
import { index as eventsIndex } from '@/routes/events';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { isCurrentUrl } = useCurrentUrl();

const navLinks = [
    { label: 'Inicio', href: home() },
    { label: 'Cómo funciona', href: howItWorks() },
    { label: 'Eventos', href: eventsIndex() },
];

const scrolled = ref(false);

function handleScroll() {
    scrolled.value = window.scrollY > 12;
}

onMounted(() => {
    handleScroll();
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
        :class="
            scrolled
                ? 'border-b border-legacy-titanium/10 bg-legacy-ink/85 backdrop-blur-md'
                : 'border-b border-transparent bg-gradient-to-b from-legacy-ink/70 to-transparent'
        "
    >
        <div
            class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <Link :href="home()" class="shrink-0">
                <FinisherLegacyLogo variant="mark" size="md" />
            </Link>

            <nav class="hidden items-center gap-8 md:flex">
                <Link
                    v-for="link in navLinks"
                    :key="link.label"
                    :href="link.href"
                    class="text-sm font-medium tracking-wide text-legacy-titanium transition-colors hover:text-legacy-copper-soft"
                    :class="{ 'text-legacy-copper-soft': isCurrentUrl(link.href) }"
                >
                    {{ link.label }}
                </Link>
            </nav>

            <div class="hidden items-center gap-3 md:flex">
                <template v-if="user">
                    <Link
                        :href="dashboard()"
                        class="text-sm font-medium text-legacy-titanium transition-colors hover:text-legacy-copper-soft"
                    >
                        Mi Legacy
                    </Link>
                    <Button
                        as-child
                        class="bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                    >
                        <Link :href="dashboard()">Dashboard</Link>
                    </Button>
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="text-sm font-medium text-legacy-titanium transition-colors hover:text-legacy-copper-soft"
                    >
                        Iniciar sesión
                    </Link>
                    <Button
                        as-child
                        class="bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                    >
                        <Link :href="register()">Crear mi Legacy</Link>
                    </Button>
                </template>
            </div>

            <Sheet>
                <SheetTrigger as-child class="md:hidden">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-legacy-bone hover:bg-white/10 hover:text-legacy-copper-soft"
                        aria-label="Abrir menú"
                    >
                        <Menu class="size-5" />
                    </Button>
                </SheetTrigger>
                <SheetContent
                    side="right"
                    class="border-legacy-titanium/10 bg-legacy-ink text-legacy-bone"
                >
                    <SheetTitle class="sr-only">Menú</SheetTitle>
                    <div class="mt-10 flex flex-col gap-1 px-2">
                        <SheetClose
                            v-for="link in navLinks"
                            :key="link.label"
                            as-child
                        >
                            <Link
                                :href="link.href"
                                class="rounded-md px-3 py-3 text-base font-medium text-legacy-bone/85 transition-colors hover:bg-white/5 hover:text-legacy-copper-soft"
                            >
                                {{ link.label }}
                            </Link>
                        </SheetClose>

                        <div class="my-3 border-t border-legacy-titanium/10" />

                        <template v-if="user">
                            <SheetClose as-child>
                                <Link
                                    :href="dashboard()"
                                    class="rounded-md px-3 py-3 text-base font-medium text-legacy-bone/85 transition-colors hover:bg-white/5 hover:text-legacy-copper-soft"
                                >
                                    Mi Legacy
                                </Link>
                            </SheetClose>
                            <SheetClose as-child class="mt-2 px-3">
                                <Button
                                    as-child
                                    class="w-full bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                                >
                                    <Link :href="dashboard()">Dashboard</Link>
                                </Button>
                            </SheetClose>
                        </template>
                        <template v-else>
                            <SheetClose as-child>
                                <Link
                                    :href="login()"
                                    class="rounded-md px-3 py-3 text-base font-medium text-legacy-bone/85 transition-colors hover:bg-white/5 hover:text-legacy-copper-soft"
                                >
                                    Iniciar sesión
                                </Link>
                            </SheetClose>
                            <SheetClose as-child class="mt-2 px-3">
                                <Button
                                    as-child
                                    class="w-full bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                                >
                                    <Link :href="register()"
                                        >Crear mi Legacy</Link
                                    >
                                </Button>
                            </SheetClose>
                        </template>
                    </div>
                </SheetContent>
            </Sheet>
        </div>
    </header>
</template>
