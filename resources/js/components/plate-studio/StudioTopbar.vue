<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, GitBranch, Save } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

defineProps<{
    templateName: string;
    version: number;
    status: 'draft' | 'published' | 'archived';
    editable: boolean;
    saving: boolean;
    zoom: number;
}>();

const emit = defineEmits<{
    save: [];
    publish: [];
    'new-version': [];
    'update:zoom': [value: number];
}>();

const statusLabel: Record<string, string> = {
    draft: 'Borrador',
    published: 'Publicado',
    archived: 'Archivado',
};
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-3 border-b border-white/10 bg-fl-graphite/40 px-4 py-3"
    >
        <Link href="/admin/plate-studio" class="text-white/50 hover:text-white">
            <ArrowLeft class="size-4" />
        </Link>

        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-white">
                {{ templateName }}
            </p>
            <p class="text-xs text-white/40">Versión {{ version }}</p>
        </div>

        <Badge
            variant="outline"
            :class="
                status === 'published'
                    ? 'border-emerald-500/30 text-emerald-400'
                    : status === 'archived'
                      ? 'border-white/20 text-white/40'
                      : 'border-amber-500/30 text-amber-400'
            "
        >
            {{ statusLabel[status] }}
        </Badge>

        <div class="ml-auto flex items-center gap-2">
            <Select
                :model-value="String(zoom)"
                @update:model-value="(v) => emit('update:zoom', Number(v))"
            >
                <SelectTrigger
                    class="w-24 border-white/10 bg-fl-black text-white"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="50">50%</SelectItem>
                    <SelectItem value="75">75%</SelectItem>
                    <SelectItem value="100">100%</SelectItem>
                    <SelectItem value="125">125%</SelectItem>
                    <SelectItem value="150">150%</SelectItem>
                </SelectContent>
            </Select>

            <template v-if="editable">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            class="border-white/15 text-white hover:bg-white/10"
                            :disabled="saving"
                            @click="emit('save')"
                        >
                            <Spinner v-if="saving" />
                            <Save v-else class="size-4" />
                            Guardar
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Ctrl/Cmd + S</TooltipContent>
                </Tooltip>
                <Button
                    size="sm"
                    class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
                    :disabled="saving"
                    @click="emit('publish')"
                >
                    <Check class="size-4" />
                    Publicar
                </Button>
            </template>
            <template v-else>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="outline"
                            size="sm"
                            class="border-white/15 text-white hover:bg-white/10"
                            @click="emit('new-version')"
                        >
                            <GitBranch class="size-4" />
                            Nueva versión
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent
                        >Esta versión ya no se puede editar. Crea una nueva a
                        partir de ella.</TooltipContent
                    >
                </Tooltip>
            </template>
        </div>
    </div>
</template>
