<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Award, Lock, MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import HelpPopover from '@/components/HelpPopover.vue';
import FinisherLegacyLogo from '@/components/public/FinisherLegacyLogo.vue';
import FinisherMascot from '@/components/public/FinisherMascot.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { claim, continueMethod } from '@/routes/legacy-code';
import type { LegacyCodeAthlete, LegacyCodePlate } from '@/types';

const { code, available, linked, ownedByMe, plate, athlete, qrUrl } =
    defineProps<{
        code: string;
        available: boolean;
        linked: boolean;
        ownedByMe: boolean;
        plate: LegacyCodePlate | null;
        athlete: LegacyCodeAthlete | null;
        qrUrl: string | null;
    }>();

const page = usePage();
const isAuthenticated = computed(() => page.props.auth.user != null);

const confirmOpen = ref(false);
const claiming = ref(false);

const formattedDate = computed(() => {
    if (!plate?.event_date) {
        return null;
    }

    return new Date(`${plate.event_date}T00:00:00`).toLocaleDateString(
        'es-MX',
        { day: 'numeric', month: 'long', year: 'numeric' },
    );
});

const initials = computed(() =>
    (plate?.athlete_name ?? 'FL')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

function submitClaim() {
    claiming.value = true;

    router.post(
        claim(code).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmOpen.value = false;
            },
            onFinish: () => {
                claiming.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="`Legacy Code ${code}`" />

    <section class="mx-auto max-w-lg px-4 py-16 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-center gap-1">
            <FinisherLegacyLogo variant="mark" size="md" />
            <HelpPopover
                title="Legacy Code"
                text="Este código es permanente. Una reimpresión de la placa conserva el mismo código — nunca cambia, sin importar cuántas veces se reimprima."
            />
        </div>

        <template v-if="!available">
            <div
                class="rounded-2xl border border-white/10 bg-legacy-carbon/50 p-8 text-center"
            >
                <div
                    class="mx-auto flex size-16 items-center justify-center rounded-full border border-legacy-titanium/15 bg-legacy-ink text-legacy-titanium/60"
                >
                    <Lock class="size-7" />
                </div>
                <p
                    class="mt-6 rounded-md border border-legacy-titanium/10 bg-legacy-ink px-3 py-1.5 font-mono text-sm text-legacy-titanium/60"
                >
                    {{ code }}
                </p>
                <h1 class="mt-6 text-2xl font-bold text-white">
                    Este Legacy Code no está disponible
                </h1>
                <p class="mt-3 text-sm text-white/60">
                    Si crees que esto es un error, contáctanos y con gusto lo
                    revisamos.
                </p>
            </div>
        </template>

        <template v-else-if="!plate">
            <div
                class="rounded-2xl border border-white/10 bg-legacy-carbon/50 p-8 text-center"
            >
                <div
                    class="mx-auto flex size-20 items-center justify-center rounded-xl border border-legacy-copper/30 bg-white p-2"
                >
                    <img
                        v-if="qrUrl"
                        :src="qrUrl"
                        alt="Código QR de este Legacy Code"
                        class="size-full"
                    />
                </div>
                <p
                    class="mt-6 rounded-md border border-legacy-titanium/10 bg-legacy-ink px-3 py-1.5 font-mono text-sm text-legacy-copper-soft"
                >
                    {{ code }}
                </p>
                <h1 class="mt-6 text-2xl font-bold text-white">
                    Este Legacy Code aún no tiene una placa asignada
                </h1>
                <p class="mt-3 text-sm text-white/60">
                    Vuelve a intentarlo más tarde.
                </p>
            </div>
        </template>

        <template v-else>
            <!-- Athlete card -->
            <div
                class="flex items-center gap-4 rounded-2xl border border-white/10 bg-legacy-carbon/50 p-5"
            >
                <div
                    class="flex size-14 shrink-0 items-center justify-center rounded-full border-2 border-legacy-copper/40 bg-legacy-ink text-base font-semibold text-legacy-copper-soft"
                >
                    {{ initials }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-white">
                        {{ plate.athlete_name ?? 'Placa Finisher Legacy' }}
                    </p>
                    <p
                        v-if="athlete"
                        class="flex items-center gap-1 text-sm text-white/50"
                    >
                        <span v-if="athlete.sport">{{ athlete.sport }}</span>
                        <span v-if="athlete.sport && athlete.city">·</span>
                        <span
                            v-if="athlete.city"
                            class="inline-flex items-center gap-1"
                        >
                            <MapPin class="size-3" />{{ athlete.city }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Big gold result card -->
            <div
                class="mt-4 rounded-2xl border-2 border-legacy-copper/40 bg-gradient-to-b from-legacy-copper/10 to-legacy-carbon/40 p-6"
            >
                <p
                    v-if="plate.event_name"
                    class="text-xl leading-tight font-bold text-white"
                >
                    {{ plate.event_name }}
                </p>
                <p
                    v-if="formattedDate"
                    class="mt-1 text-sm text-white/50 capitalize"
                >
                    {{ formattedDate }}
                </p>

                <p
                    v-if="plate.official_time"
                    class="mt-5 legacy-numeric font-mono text-4xl font-bold text-legacy-copper-soft"
                >
                    {{ plate.official_time }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        v-if="plate.race_name"
                        class="rounded-full border border-legacy-titanium/15 bg-legacy-ink px-3 py-1 text-xs font-medium text-legacy-titanium"
                    >
                        {{ plate.race_name }}
                    </span>
                    <span
                        v-if="plate.pace"
                        class="rounded-full border border-legacy-titanium/15 bg-legacy-ink px-3 py-1 text-xs font-medium text-legacy-titanium"
                    >
                        Ritmo {{ plate.pace }}
                    </span>
                </div>
            </div>

            <div
                class="mt-4 rounded-xl border border-dashed border-white/15 p-5 text-center"
            >
                <template v-if="ownedByMe">
                    <FinisherMascot
                        variant="success"
                        alt=""
                        class="fl-success-pulse mx-auto mb-1"
                    />
                    <p class="text-base font-semibold text-white">
                        Esta historia ya es parte de tu Legacy.
                    </p>
                    <p class="mt-1 text-sm text-white/60">
                        Podrás encontrarla en tu Legacy Profile en cualquier
                        momento.
                    </p>
                </template>
                <template v-else-if="linked">
                    <Award class="mx-auto size-5 text-legacy-copper-soft" />
                    <p class="mt-2 text-sm text-white/70">
                        Esta placa ya forma parte de un Legacy Profile.
                    </p>
                </template>
                <template v-else-if="isAuthenticated">
                    <p class="text-sm text-white/70">
                        Esta placa está esperando formar parte de un Legacy.
                    </p>
                    <Button
                        class="fl-hover-lift mt-4 w-full bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                        @click="confirmOpen = true"
                    >
                        Vincular a mi Legacy
                    </Button>
                </template>
                <template v-else>
                    <p class="text-sm text-white/70">
                        Esta placa está esperando formar parte de un Legacy.
                    </p>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                        <Button
                            as-child
                            variant="outline"
                            class="fl-hover-lift w-full border-white/15 text-white hover:bg-white/5"
                        >
                            <a
                                :href="
                                    continueMethod({ code, provider: 'login' })
                                        .url
                                "
                                >Iniciar sesión</a
                            >
                        </Button>
                        <Button
                            as-child
                            class="fl-hover-lift w-full bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                        >
                            <a
                                :href="
                                    continueMethod({
                                        code,
                                        provider: 'register',
                                    }).url
                                "
                                >Crear mi Legacy</a
                            >
                        </Button>
                    </div>
                </template>
            </div>
        </template>

        <Dialog v-model:open="confirmOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>¿Vincular esta placa a tu Legacy?</DialogTitle>
                    <DialogDescription>
                        {{ plate?.event_name ?? 'Esta placa' }} pasará a formar
                        parte de tu Legacy Profile de forma permanente. Esta
                        acción no se puede deshacer.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button
                        variant="secondary"
                        :disabled="claiming"
                        @click="confirmOpen = false"
                    >
                        Cancelar
                    </Button>
                    <Button
                        class="bg-legacy-copper text-legacy-bone hover:bg-legacy-copper-soft"
                        :disabled="claiming"
                        @click="submitClaim"
                    >
                        <Spinner v-if="claiming" />
                        Confirmar y vincular
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>
