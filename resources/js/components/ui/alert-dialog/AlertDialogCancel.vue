<script setup lang="ts">
import type { AlertDialogCancelProps } from 'reka-ui';
import { AlertDialogCancel } from 'reka-ui';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = defineProps<AlertDialogCancelProps & { class?: string }>();
</script>

<template>
    <AlertDialogCancel data-slot="alert-dialog-cancel" v-bind="props" :class="cn(buttonVariants({ variant: 'outline' }), props.class)">
        <slot />
    </AlertDialogCancel>
</template>
