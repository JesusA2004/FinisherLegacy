<script setup lang="ts">
/**
 * Bottom tab bar shown only below `lg` — the shadcn Sidebar already covers
 * desktop/tablet via its collapsible+offcanvas modes, but on phones an
 * app-style bottom nav reads far more like a native app than a drawer.
 */
import { Link } from '@inertiajs/vue3';
import { Award, Compass, LayoutGrid, Plus, QrCode, UserCircle } from '@lucide/vue';
import { ref } from 'vue';
import QrScannerDialog from '@/components/qr/QrScannerDialog.vue';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import { create as createMedal, index as medalsIndex } from '@/routes/dashboard/medals';
import { edit as editProfile } from '@/routes/dashboard/profile';
import { index as eventsIndex } from '@/routes/events';

const { isCurrentUrl } = useCurrentUrl();

const sheetOpen = ref(false);
const scannerOpen = ref(false);

function openScanner() {
    sheetOpen.value = false;
    scannerOpen.value = true;
}

const tabs = [
    { label: 'Inicio', href: dashboard(), icon: LayoutGrid },
    { label: 'Eventos', href: eventsIndex(), icon: Compass },
];

const tabsRight = [
    { label: 'Medallas', href: medalsIndex(), icon: Award },
    { label: 'Perfil', href: editProfile(), icon: UserCircle },
];
</script>

<template>
    <QrScannerDialog v-model:open="scannerOpen" />

    <nav
        class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-white/10 bg-fl-black/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-sm lg:hidden"
    >
        <Link
            v-for="tab in tabs"
            :key="tab.label"
            :href="tab.href"
            class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-[11px] transition-colors"
            :class="isCurrentUrl(tab.href) ? 'text-fl-gold' : 'text-white/40'"
        >
            <component :is="tab.icon" class="size-5" />
            {{ tab.label }}
        </Link>

        <button
            type="button"
            class="fl-hover-glow -mt-6 flex size-14 shrink-0 items-center justify-center rounded-full border-4 border-fl-black bg-fl-gold text-fl-black shadow-lg transition-transform active:scale-95"
            aria-label="Agregar"
            @click="sheetOpen = true"
        >
            <Plus class="size-6" />
        </button>

        <Link
            v-for="tab in tabsRight"
            :key="tab.label"
            :href="tab.href"
            class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-[11px] transition-colors"
            :class="isCurrentUrl(tab.href) ? 'text-fl-gold' : 'text-white/40'"
        >
            <component :is="tab.icon" class="size-5" />
            {{ tab.label }}
        </Link>
    </nav>

    <Sheet v-model:open="sheetOpen">
        <SheetContent side="bottom" class="dark border-white/10 bg-fl-graphite">
            <SheetHeader>
                <SheetTitle class="text-white">¿Qué quieres hacer?</SheetTitle>
            </SheetHeader>
            <div class="grid gap-3 p-4 pt-0">
                <button
                    type="button"
                    class="fl-hover-glow flex items-center gap-4 rounded-xl border border-fl-gold/30 bg-fl-black/60 p-4 text-left transition-colors"
                    @click="openScanner"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-full bg-fl-gold/10 text-fl-gold"
                    >
                        <QrCode class="size-5" />
                    </div>
                    <div>
                        <p class="font-semibold text-white">
                            Escanear el QR de mi placa
                        </p>
                        <p class="mt-0.5 text-xs text-white/50">
                            Carga el evento, tiempo y ritmo automáticamente.
                        </p>
                    </div>
                </button>

                <SheetClose as-child>
                    <Link
                        :href="createMedal()"
                        class="flex items-center gap-4 rounded-xl border border-white/10 bg-fl-black/40 p-4 transition-colors hover:border-white/20"
                        @click="sheetOpen = false"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/5 text-white/60"
                        >
                            <Award class="size-5" />
                        </div>
                        <div>
                            <p class="font-semibold text-white">
                                Agregar medalla manualmente
                            </p>
                            <p class="mt-0.5 text-xs text-white/50">
                                Busca tu evento o captura los datos tú mismo.
                            </p>
                        </div>
                    </Link>
                </SheetClose>
            </div>
        </SheetContent>
    </Sheet>
</template>
