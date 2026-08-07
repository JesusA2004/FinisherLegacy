<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import LegacyProfilePreview from '@/components/public/LegacyProfilePreview.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { dashboard } from '@/routes';
import { update } from '@/routes/dashboard/profile';
import type { AthleteProfileFormData, SportOption } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Mi Legacy Profile', href: update() },
        ],
    },
});

const { profile, sports } = defineProps<{
    profile: AthleteProfileFormData | null;
    sports: SportOption[];
}>();

const form = useForm({
    username: profile?.username ?? '',
    bio: profile?.bio ?? '',
    city: profile?.city ?? '',
    state: profile?.state ?? '',
    country: profile?.country ?? '',
    main_sport_id: profile?.main_sport_id
        ? String(profile.main_sport_id)
        : 'none',
    profile_visibility: profile?.profile_visibility ?? 'public',
    profile_photo: null as File | null,
    cover_photo: null as File | null,
});

const profilePhotoPreview = ref<string | null>(
    profile?.profile_photo_url ?? null,
);
const coverPhotoPreview = ref<string | null>(profile?.cover_photo_url ?? null);

function onProfilePhotoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.profile_photo = file;
    profilePhotoPreview.value = file
        ? URL.createObjectURL(file)
        : (profile?.profile_photo_url ?? null);
}

function onCoverPhotoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.cover_photo = file;
    coverPhotoPreview.value = file
        ? URL.createObjectURL(file)
        : (profile?.cover_photo_url ?? null);
}

function submit() {
    form.transform((data) => ({
        ...data,
        main_sport_id:
            data.main_sport_id === 'none' ? null : data.main_sport_id,
    })).patch(update().url, { forceFormData: true });
}

const previewProfile = computed(() => ({
    username: form.username || 'tulegacy',
    name: 'Tu Legacy Profile',
    city: form.city || null,
    country: form.country || null,
    sport:
        sports.find((s) => String(s.id) === form.main_sport_id)?.name ?? null,
    photo_url: profilePhotoPreview.value,
    medals_count: 0,
    medals: [],
}));
</script>

<template>
    <Head title="Mi Legacy Profile" />

    <div
        class="mx-auto grid max-w-5xl gap-8 p-4 md:p-6 lg:grid-cols-[1fr_320px]"
    >
        <form class="space-y-6" @submit.prevent="submit">
            <div>
                <h1 class="text-xl font-bold text-white">Mi Legacy Profile</h1>
                <p class="mt-1 text-sm text-white/50">
                    Así es como el mundo verá tu colección de logros.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="username">Username</Label>
                <Input
                    id="username"
                    v-model="form.username"
                    placeholder="tunombre"
                    required
                />
                <p v-if="form.errors.username" class="text-sm text-red-500">
                    {{ form.errors.username }}
                </p>
                <p v-else class="text-xs text-white/40">
                    Tu perfil público estará en /@{{
                        form.username || 'username'
                    }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="bio">Bio</Label>
                <Textarea
                    id="bio"
                    v-model="form.bio"
                    maxlength="500"
                    placeholder="Triatleta. Siempre buscando la mejor versión de mí."
                />
                <p v-if="form.errors.bio" class="text-sm text-red-500">
                    {{ form.errors.bio }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="city">Ciudad</Label>
                    <Input id="city" v-model="form.city" />
                </div>
                <div class="grid gap-2">
                    <Label for="state">Estado</Label>
                    <Input id="state" v-model="form.state" />
                </div>
                <div class="grid gap-2">
                    <Label for="country">País</Label>
                    <Input id="country" v-model="form.country" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label>Deporte principal</Label>
                    <Select v-model="form.main_sport_id">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Selecciona un deporte" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none"
                                >Sin especificar</SelectItem
                            >
                            <SelectItem
                                v-for="sport in sports"
                                :key="sport.id"
                                :value="String(sport.id)"
                            >
                                {{ sport.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-2">
                    <Label>Visibilidad</Label>
                    <Select v-model="form.profile_visibility">
                        <SelectTrigger class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="public">Público</SelectItem>
                            <SelectItem value="private">Privado</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="profile_photo">Foto de perfil</Label>
                    <Input
                        id="profile_photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onProfilePhotoChange"
                    />
                    <p
                        v-if="form.errors.profile_photo"
                        class="text-sm text-red-500"
                    >
                        {{ form.errors.profile_photo }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="cover_photo">Foto de portada</Label>
                    <Input
                        id="cover_photo"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onCoverPhotoChange"
                    />
                    <p
                        v-if="form.errors.cover_photo"
                        class="text-sm text-red-500"
                    >
                        {{ form.errors.cover_photo }}
                    </p>
                </div>
            </div>

            <Button
                type="submit"
                class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
                :disabled="form.processing"
            >
                Guardar cambios
            </Button>
        </form>

        <div>
            <p
                class="mb-3 text-sm font-semibold tracking-wide text-white/50 uppercase"
            >
                Vista previa
            </p>
            <div class="rounded-2xl bg-fl-black p-4">
                <LegacyProfilePreview :profile="previewProfile" />
            </div>
        </div>
    </div>
</template>
