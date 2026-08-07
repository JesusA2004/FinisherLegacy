<script setup lang="ts">
import { Award, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import type { LegacyProfilePreview } from '@/types';

const { profile } = defineProps<{
    profile: LegacyProfilePreview | null;
}>();

const isMock = computed(() => profile === null);

const display = computed<LegacyProfilePreview>(
    () =>
        profile ?? {
            username: 'tulegacy',
            name: 'Tu nombre aquí',
            city: 'Tu ciudad',
            country: 'México',
            sport: 'Running',
            photo_url: null,
            medals_count: 3,
            medals: [
                { title: 'Tu primera medalla', distance_label: '10K' },
                { title: 'Tu segundo logro', distance_label: '21K' },
            ],
        },
);

const initials = computed(() =>
    display.value.name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <div class="relative mx-auto w-full max-w-sm">
        <div
            class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-fl-gold/10 via-transparent to-transparent blur-2xl"
        />
        <div
            class="relative overflow-hidden rounded-2xl border border-white/10 bg-fl-graphite/70 p-6 backdrop-blur-sm"
        >
            <span
                v-if="isMock"
                class="absolute top-4 right-4 rounded-full border border-white/10 bg-fl-black px-2.5 py-1 text-[10px] font-medium tracking-wide text-white/40 uppercase"
            >
                Vista previa
            </span>

            <div class="flex flex-col items-center text-center">
                <div
                    class="flex size-20 items-center justify-center overflow-hidden rounded-full border-2 border-fl-gold/40 bg-fl-black text-xl font-semibold text-fl-gold"
                >
                    <img
                        v-if="display.photo_url"
                        :src="display.photo_url"
                        :alt="display.name"
                        class="size-full object-cover"
                    />
                    <span v-else>{{ initials }}</span>
                </div>

                <h3 class="mt-4 text-lg font-semibold text-white">
                    {{ display.name }}
                </h3>
                <p class="text-sm text-fl-gold">@{{ display.username }}</p>

                <div
                    class="mt-2 flex items-center gap-1.5 text-xs text-white/50"
                >
                    <MapPin class="size-3.5" />
                    <span>{{ display.city }}, {{ display.country }}</span>
                    <template v-if="display.sport">
                        <span aria-hidden="true">·</span>
                        <span>{{ display.sport }}</span>
                    </template>
                </div>

                <div
                    class="mt-5 flex items-center gap-1.5 rounded-full border border-white/10 bg-fl-black px-4 py-1.5"
                >
                    <Award class="size-4 text-fl-gold" />
                    <span class="text-sm font-medium text-white">
                        {{ display.medals_count }} medallas
                    </span>
                </div>

                <div
                    v-if="display.medals.length"
                    class="mt-5 grid w-full grid-cols-2 gap-2"
                >
                    <div
                        v-for="medal in display.medals"
                        :key="medal.title"
                        class="rounded-lg border border-white/10 bg-fl-black/60 px-3 py-2 text-left"
                    >
                        <p class="truncate text-xs font-medium text-white/80">
                            {{ medal.title }}
                        </p>
                        <p
                            v-if="medal.distance_label"
                            class="text-[11px] text-fl-gold"
                        >
                            {{ medal.distance_label }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
