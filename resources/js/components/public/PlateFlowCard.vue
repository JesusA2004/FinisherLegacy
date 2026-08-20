<script setup lang="ts">
import { ChevronRight } from '@lucide/vue';

withDefaults(
    defineProps<{
        eyebrow: string;
        title: string;
        description: string;
        chain: string[];
        highlighted?: boolean;
    }>(),
    {
        highlighted: false,
    },
);
</script>

<template>
    <div
        class="flex flex-col gap-6 rounded-2xl border p-8 transition-colors"
        :class="
            highlighted
                ? 'border-legacy-copper/40 bg-gradient-to-b from-legacy-copper/10 to-legacy-carbon/40'
                : 'border-legacy-titanium/10 bg-legacy-carbon/40'
        "
    >
        <span
            class="text-xs font-semibold tracking-[0.25em] uppercase"
            :class="highlighted ? 'text-legacy-copper-soft' : 'text-legacy-titanium/50'"
        >
            {{ eyebrow }}
        </span>

        <h3 class="text-2xl font-bold text-legacy-bone">{{ title }}</h3>

        <div class="flex flex-wrap items-center gap-2">
            <template v-for="(step, index) in chain" :key="step">
                <span
                    class="rounded-full border px-3 py-1.5 text-sm font-medium"
                    :class="
                        highlighted
                            ? 'border-legacy-copper/40 bg-legacy-ink text-legacy-copper-soft'
                            : 'border-legacy-titanium/15 bg-legacy-ink text-legacy-titanium'
                    "
                >
                    {{ step }}
                </span>
                <ChevronRight
                    v-if="index < chain.length - 1"
                    class="size-4 shrink-0 text-legacy-titanium/25"
                />
            </template>
        </div>

        <p class="text-sm leading-relaxed text-legacy-titanium">
            {{ description }}
        </p>
    </div>
</template>
