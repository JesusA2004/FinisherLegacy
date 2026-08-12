<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, Check, Printer, QrCode, Wrench } from '@lucide/vue';
import { ref } from 'vue';
import { testExport } from '@/actions/App/Http/Controllers/Admin/PlateStudioController';
import {
    assignTemplate,
    markQrTested,
} from '@/actions/App/Http/Controllers/Admin/ProductionSetupController';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';

type VersionOption = { id: number; label: string };

const props = defineProps<{
    edition: { id: number; name: string; status: string };
    races: { id: number; name: string; assignment: number | null }[];
    availableVersions: VersionOption[];
    defaultAssignment: { plate_template_version_id: number; template_name: string; version: number } | null;
    checklist: {
        template_assigned: boolean;
        version_published: boolean;
        qr_tested_at: string | null;
        qr_tested_by: string | null;
    };
}>();

const selectedVersion = ref<string>(props.defaultAssignment ? String(props.defaultAssignment.plate_template_version_id) : '');
const confirmOpen = ref(false);
const notes = ref('');

function requestAssign() {
    if (props.edition.status === 'in_progress' && props.defaultAssignment) {
        confirmOpen.value = true;

        return;
    }

    doAssign();
}

function doAssign() {
    confirmOpen.value = false;
    router.post(assignTemplate.url(props.edition.id), { plate_template_version_id: selectedVersion.value }, { preserveScroll: true });
}

function assignRace(raceId: number, versionId: string) {
    router.post(assignTemplate.url(props.edition.id), { plate_template_version_id: versionId, event_race_id: raceId }, { preserveScroll: true });
}

function submitQrTest() {
    router.post(markQrTested.url(props.edition.id), { notes: notes.value }, { preserveScroll: true, onSuccess: () => (notes.value = '') });
}
</script>

<template>
    <Head :title="`Producción — ${edition.name}`" />

    <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
        <div>
            <p class="text-xs tracking-wide text-white/40 uppercase">
                Preparar evento para producción
            </p>
            <h1 class="text-xl font-bold text-white">{{ edition.name }}</h1>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-2 rounded-lg border border-white/10 bg-fl-graphite/40 p-3">
                <Check v-if="checklist.template_assigned" class="size-4 shrink-0 text-emerald-400" />
                <AlertTriangle v-else class="size-4 shrink-0 text-amber-400" />
                <span class="text-sm text-white/80">Molde asignado</span>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-white/10 bg-fl-graphite/40 p-3">
                <Check v-if="checklist.version_published" class="size-4 shrink-0 text-emerald-400" />
                <AlertTriangle v-else class="size-4 shrink-0 text-amber-400" />
                <span class="text-sm text-white/80">Versión publicada</span>
            </div>
            <div class="flex items-center gap-2 rounded-lg border border-white/10 bg-fl-graphite/40 p-3">
                <Check v-if="checklist.qr_tested_at" class="size-4 shrink-0 text-emerald-400" />
                <AlertTriangle v-else class="size-4 shrink-0 text-amber-400" />
                <span class="text-sm text-white/80">QR probado</span>
            </div>
        </div>

        <Separator class="bg-white/10" />

        <section class="space-y-3">
            <h2 class="text-sm font-semibold text-white">Molde principal del evento</h2>
            <p class="text-xs text-white/40">
                Todas las placas del evento usan este diseño por defecto. Solo cambian los datos de cada corredor.
            </p>

            <div v-if="!availableVersions.length" class="rounded-lg border border-dashed border-white/15 p-4 text-sm text-white/50">
                No hay ninguna versión publicada todavía. Publica un molde en Plate Studio primero.
            </div>
            <div v-else class="flex gap-2">
                <Select v-model="selectedVersion">
                    <SelectTrigger class="w-full border-white/10 bg-fl-black text-white">
                        <SelectValue placeholder="Selecciona un molde publicado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="version in availableVersions" :key="version.id" :value="String(version.id)">
                            {{ version.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft" :disabled="!selectedVersion" @click="requestAssign">
                    Asignar
                </Button>
            </div>

            <p v-if="defaultAssignment" class="text-xs text-white/50">
                Actual: {{ defaultAssignment.template_name }} — V{{ defaultAssignment.version }}
            </p>

            <div v-if="defaultAssignment" class="flex gap-2">
                <Button as-child variant="outline" size="sm" class="border-white/15 text-white hover:bg-white/10">
                    <a :href="testExport.url([defaultAssignment.plate_template_version_id, 'front'])" target="_blank">
                        <Printer class="size-3.5" />
                        Prueba de grabado — frente
                    </a>
                </Button>
                <Button as-child variant="outline" size="sm" class="border-white/15 text-white hover:bg-white/10">
                    <a :href="testExport.url([defaultAssignment.plate_template_version_id, 'back'])" target="_blank">
                        <Printer class="size-3.5" />
                        reverso
                    </a>
                </Button>
            </div>
        </section>

        <template v-if="races.length">
            <Separator class="bg-white/10" />
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-white">Personalizar por distancia (opcional)</h2>
                <div v-for="race in races" :key="race.id" class="flex items-center justify-between gap-2 rounded-lg border border-white/10 p-3">
                    <span class="text-sm text-white/80">{{ race.name }}</span>
                    <Select :model-value="race.assignment ? String(race.assignment) : undefined" @update:model-value="(v) => assignRace(race.id, String(v))">
                        <SelectTrigger class="w-56 border-white/10 bg-fl-black text-white">
                            <SelectValue placeholder="Usar molde principal" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="version in availableVersions" :key="version.id" :value="String(version.id)">
                                {{ version.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </section>
        </template>

        <Separator class="bg-white/10" />

        <section class="space-y-3">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-white">
                <QrCode class="size-4" />
                Prueba física de QR
            </h2>
            <p class="text-xs text-white/40">
                El software solo valida la estructura del código. Antes de producir en volumen, graba una muestra y
                escanéala con un teléfono real.
            </p>

            <p v-if="checklist.qr_tested_at" class="flex items-center gap-2 text-sm text-emerald-400">
                <Check class="size-4" />
                Probado el {{ new Date(checklist.qr_tested_at).toLocaleString('es-MX') }}
                <span v-if="checklist.qr_tested_by">por {{ checklist.qr_tested_by }}</span>
            </p>

            <Textarea v-model="notes" placeholder="Notas de la prueba (opcional)" class="border-white/10 bg-fl-black text-white" />
            <Button variant="outline" class="border-white/15 text-white hover:bg-white/10" @click="submitQrTest">
                <Wrench class="size-4" />
                Marcar prueba física realizada
            </Button>
        </section>

        <AlertDialog v-model:open="confirmOpen">
            <AlertDialogContent class="dark border-white/10 bg-fl-graphite text-white">
                <AlertDialogHeader>
                    <AlertDialogTitle>El evento está en curso</AlertDialogTitle>
                    <AlertDialogDescription class="text-white/60">
                        Cambiar el molde principal ahora puede provocar que las primeras placas tengan un diseño y el
                        resto otro. ¿Seguro que quieres continuar?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel class="border-white/15 bg-transparent text-white hover:bg-white/10">Cancelar</AlertDialogCancel>
                    <AlertDialogAction class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft" @click="doAssign">
                        Sí, cambiar el molde
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </div>
</template>
