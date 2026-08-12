<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Copy,
    Download,
    ExternalLink,
    LayoutGrid,
    Plus,
    QrCode,
} from '@lucide/vue';
import { ref } from 'vue';
import DownloadPlateDialog from '@/components/plates/DownloadPlateDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    plate: {
        id: number;
        serial_number: string;
        legacy_code: string | null;
        qr_url: string | null;
        status: string;
    };
    generateAnotherHref: string;
}>();

const downloadOpen = ref(false);
const copied = ref(false);

function copyCode() {
    if (!props.plate.legacy_code) {
        return;
    }

    navigator.clipboard.writeText(props.plate.legacy_code);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}
</script>

<template>
    <div>
        <div
            class="space-y-3 rounded-xl border border-fl-gold/20 bg-fl-gold/5 p-4"
        >
            <p class="flex items-center gap-2 text-sm text-fl-gold">
                <CheckCircle2 class="size-4" />
                Placa generada — {{ plate.status }}
            </p>
            <p class="font-mono text-sm text-white">
                Serial: {{ plate.serial_number }}
            </p>
            <div class="flex items-center gap-2">
                <p class="font-mono text-sm text-white/80">
                    {{ plate.legacy_code }}
                </p>
                <button
                    class="text-white/40 hover:text-white"
                    title="Copiar Legacy Code"
                    @click="copyCode"
                >
                    <Copy class="size-3.5" />
                </button>
                <Badge
                    v-if="copied"
                    variant="outline"
                    class="border-emerald-500/30 text-emerald-400"
                    >Copiado</Badge
                >
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2">
            <Button
                class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
                @click="downloadOpen = true"
            >
                <Download class="size-4" />
                Descargar para láser
            </Button>
            <Button
                as-child
                variant="outline"
                class="border-white/15 text-white hover:bg-white/10"
            >
                <a :href="plate.qr_url ?? '#'" target="_blank" download>
                    <QrCode class="size-4" />
                    Descargar QR
                </a>
            </Button>
            <Button
                as-child
                variant="outline"
                class="border-white/15 text-white hover:bg-white/10"
            >
                <Link href="/production">
                    <LayoutGrid class="size-4" />
                    Ver producción
                </Link>
            </Button>
            <Button
                as-child
                variant="outline"
                class="border-white/15 text-white hover:bg-white/10"
            >
                <a :href="`/l/${plate.legacy_code}`" target="_blank">
                    <ExternalLink class="size-4" />
                    Abrir Legacy Page
                </a>
            </Button>
        </div>

        <Button
            variant="ghost"
            class="mt-2 w-full text-white/60 hover:text-white"
            @click="router.visit(generateAnotherHref)"
        >
            <Plus class="size-4" />
            Generar otra
        </Button>

        <DownloadPlateDialog v-model:open="downloadOpen" :plate-id="plate.id" />
    </div>
</template>
