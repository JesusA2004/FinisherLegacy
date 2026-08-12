<script setup lang="ts">
import { Download } from '@lucide/vue';
import { ref } from 'vue';
import { exportMethod as exportFace, exportPackage } from '@/actions/App/Http/Controllers/Admin/PlateController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const props = defineProps<{ plateId: number }>();
const open = defineModel<boolean>('open', { default: false });

const format = ref<'svg' | 'png' | 'pdf' | 'zip'>('svg');
const dpi = ref('300');
const face = ref<'front' | 'back'>('front');

function download() {
    if (format.value === 'zip') {
        window.open(exportPackage.url(props.plateId), '_blank');
    } else {
        window.open(
            exportFace.url([props.plateId, face.value, format.value], {
                query: format.value === 'png' ? { dpi: dpi.value } : {},
            }),
            '_blank',
        );
    }

    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="dark border-white/10 bg-fl-graphite text-white">
            <DialogHeader>
                <DialogTitle>Descargar archivos de grabado</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
                <div class="grid gap-2">
                    <Label>Formato</Label>
                    <Select v-model="format">
                        <SelectTrigger class="w-full border-white/10 bg-fl-black text-white">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="svg">SVG (vector, recomendado para láser)</SelectItem>
                            <SelectItem value="png">PNG (imagen)</SelectItem>
                            <SelectItem value="pdf">PDF (tamaño físico exacto)</SelectItem>
                            <SelectItem value="zip">Paquete completo (ZIP)</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="format !== 'zip'" class="grid gap-2">
                    <Label>Cara</Label>
                    <Select v-model="face">
                        <SelectTrigger class="w-full border-white/10 bg-fl-black text-white">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="front">Frente</SelectItem>
                            <SelectItem value="back">Reverso</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div v-if="format === 'png'" class="grid gap-2">
                    <Label>Resolución</Label>
                    <Select v-model="dpi">
                        <SelectTrigger class="w-full border-white/10 bg-fl-black text-white">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="300">300 DPI</SelectItem>
                            <SelectItem value="600">600 DPI</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <p v-if="format === 'zip'" class="text-xs text-white/50">
                    Incluye frente, reverso y QR en SVG, más metadata.json con serial, Legacy Code y evento.
                </p>
            </div>

            <DialogFooter>
                <Button class="w-full bg-fl-gold text-fl-black hover:bg-fl-gold-soft" @click="download">
                    <Download class="size-4" />
                    Descargar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
