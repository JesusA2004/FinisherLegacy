<script setup lang="ts">
import { HelpCircle } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

defineProps<{
    title: string;
    text: string;
}>();
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="size-7 text-white/40 hover:bg-white/10 hover:text-fl-gold"
                :title="`Ayuda: ${title}`"
            >
                <HelpCircle class="size-4" />
            </Button>
        </PopoverTrigger>
        <PopoverContent
            class="w-72 border-white/10 bg-fl-graphite p-4"
            side="bottom"
            align="start"
        >
            <p class="text-sm font-semibold text-fl-gold">{{ title }}</p>
            <p class="mt-1.5 text-sm text-white/70">{{ text }}</p>
        </PopoverContent>
    </Popover>
</template>
