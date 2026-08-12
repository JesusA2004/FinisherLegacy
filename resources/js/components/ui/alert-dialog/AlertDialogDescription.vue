<script setup lang="ts">
import type { AlertDialogDescriptionProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { AlertDialogDescription } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<AlertDialogDescriptionProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
    <AlertDialogDescription data-slot="alert-dialog-description" v-bind="props" :class="cn('text-muted-foreground text-sm', props.class)">
        <slot />
    </AlertDialogDescription>
</template>
