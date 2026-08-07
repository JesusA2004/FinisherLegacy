<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Award, MapPin, Trophy } from '@lucide/vue';
import { computed } from 'vue';
import MascotEmptyState from '@/components/public/MascotEmptyState.vue';
import { Button } from '@/components/ui/button';
import { edit as editProfile } from '@/routes/dashboard/profile';
import type { PublicAthleteMedal, PublicAthleteProfile } from '@/types';

const { isOwner, profile, stats, medals } = defineProps<{
    isOwner: boolean;
    profile: PublicAthleteProfile;
    stats: { medals: number; events: number };
    medals: PublicAthleteMedal[];
}>();

const initials = computed(() =>
    profile.name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <Head :title="`${profile.name} (@${profile.username})`">
        <meta
            name="description"
            :content="
                profile.bio ??
                `El Legacy Profile de ${profile.name} en Finisher Legacy.`
            "
        />
    </Head>

    <section>
        <div
            class="relative h-48 w-full overflow-hidden bg-gradient-to-br from-fl-graphite-light via-fl-graphite to-fl-black sm:h-64"
        >
            <img
                v-if="profile.cover_url"
                :src="profile.cover_url"
                :alt="`Portada de ${profile.name}`"
                class="size-full object-cover"
            />
        </div>

        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="-mt-14 flex items-end justify-between">
                <div
                    class="flex size-28 items-center justify-center overflow-hidden rounded-full border-4 border-fl-black bg-fl-graphite text-3xl font-semibold text-fl-gold"
                >
                    <img
                        v-if="profile.photo_url"
                        :src="profile.photo_url"
                        :alt="profile.name"
                        class="size-full object-cover"
                    />
                    <span v-else>{{ initials }}</span>
                </div>

                <Button
                    v-if="isOwner"
                    as-child
                    variant="outline"
                    class="mb-2 border-white/20 bg-fl-black text-white hover:bg-white/10 hover:text-white"
                >
                    <Link :href="editProfile()">Editar mi Legacy Profile</Link>
                </Button>
            </div>

            <div class="mt-4">
                <h1 class="text-2xl font-bold text-white">
                    {{ profile.name }}
                </h1>
                <p class="text-fl-gold">@{{ profile.username }}</p>

                <p
                    v-if="profile.city || profile.country"
                    class="mt-1 flex items-center gap-1 text-sm text-white/50"
                >
                    <MapPin class="size-3.5" />
                    {{
                        [profile.city, profile.country]
                            .filter(Boolean)
                            .join(', ')
                    }}
                </p>

                <p
                    v-if="profile.bio"
                    class="mt-4 max-w-xl leading-relaxed text-white/70"
                >
                    {{ profile.bio }}
                </p>
            </div>

            <div class="mt-6 flex gap-8 border-y border-white/10 py-4">
                <div>
                    <p class="text-xl font-bold text-white">
                        {{ stats.medals }}
                    </p>
                    <p class="text-xs text-white/40 uppercase">Medallas</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-white">
                        {{ stats.events }}
                    </p>
                    <p class="text-xs text-white/40 uppercase">Eventos</p>
                </div>
                <div v-if="profile.sport">
                    <p class="text-xl font-bold text-white">
                        {{ profile.sport }}
                    </p>
                    <p class="text-xs text-white/40 uppercase">Deporte</p>
                </div>
            </div>

            <div class="py-10">
                <h2
                    class="mb-5 flex items-center gap-2 text-sm font-semibold tracking-wide text-white/60 uppercase"
                >
                    <Trophy class="size-4 text-fl-gold" />
                    Colección de medallas
                </h2>

                <div
                    v-if="medals.length"
                    class="grid grid-cols-2 gap-4 sm:grid-cols-3"
                >
                    <div
                        v-for="medal in medals"
                        :key="medal.id"
                        class="group overflow-hidden rounded-xl border border-white/10 bg-fl-graphite/40"
                    >
                        <div
                            class="aspect-square bg-gradient-to-br from-fl-graphite-light to-fl-black"
                        >
                            <img
                                v-if="medal.thumbnail_url"
                                :src="medal.thumbnail_url"
                                :alt="medal.title"
                                loading="lazy"
                                class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <div
                                v-else
                                class="flex size-full items-center justify-center"
                            >
                                <Award class="size-8 text-white/15" />
                            </div>
                        </div>
                        <div class="p-3">
                            <p class="truncate text-sm font-medium text-white">
                                {{ medal.title }}
                            </p>
                            <p class="text-xs text-white/40">
                                {{ medal.distance_label }}
                            </p>
                        </div>
                    </div>
                </div>

                <MascotEmptyState
                    v-else
                    title="Todavía no hay medallas públicas aquí."
                    :description="
                        isOwner
                            ? 'Las medallas que marques como públicas aparecerán en esta vitrina.'
                            : undefined
                    "
                />
            </div>
        </div>
    </section>
</template>
