<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Copy } from '@lucide/vue';
import { ref } from 'vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Tu Legacy comienza aquí',
        description:
            'Confirma tu correo dando clic en el enlace que te enviamos.',
    },
});

const { status, legacyId } = defineProps<{
    status?: string;
    legacyId?: string | null;
}>();

const copied = ref(false);

async function copyLegacyId() {
    if (!legacyId) {
return;
}

    await navigator.clipboard.writeText(legacyId);
    copied.value = true;
    setTimeout(() => (copied.value = false), 1800);
}
</script>

<template>
    <Head title="Verificación de correo" />

    <div
        v-if="legacyId"
        class="fl-hover-glow mb-6 rounded-xl border border-fl-gold/30 bg-gradient-to-br from-fl-gold/10 to-transparent p-5 text-center transition-shadow duration-300"
    >
        <p class="text-xs font-medium tracking-widest text-white/40 uppercase">
            Tu Legacy ID
        </p>
        <div class="mt-2 flex items-center justify-center gap-2">
            <p class="font-mono text-2xl font-bold text-fl-gold">
                {{ legacyId }}
            </p>
            <button
                type="button"
                class="fl-focus-glow flex size-8 items-center justify-center rounded-full text-white/50 transition-colors hover:text-fl-gold"
                aria-label="Copiar Legacy ID"
                @click="copyLegacyId"
            >
                <Check v-if="copied" class="size-4" />
                <Copy v-else class="size-4" />
            </button>
        </div>
        <p class="mt-3 text-sm text-white/50">
            Confirma tu correo para desbloquear tu Legacy Profile.
        </p>
    </div>

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-emerald-500"
    >
        Se envió un nuevo enlace de verificación al correo con el que te
        registraste.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button
            :disabled="processing"
            class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft"
        >
            <Spinner v-if="processing" />
            Reenviar correo de verificación
        </Button>

        <TextLink
            :href="logout()"
            as="button"
            class="mx-auto block text-sm text-fl-gold decoration-fl-gold/40"
        >
            Cerrar sesión
        </TextLink>
    </Form>
</template>
