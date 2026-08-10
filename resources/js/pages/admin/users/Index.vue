<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import AdminTable from '@/components/admin/AdminTable.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type UserRow = {
    id: number;
    name: string;
    email: string;
    status: string;
    roles: string;
    last_login_at: string;
};

const { users, roles, filters, currentUserId, currentUserIsSuperAdmin } = defineProps<{
    users: {
        data: UserRow[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    roles: string[];
    currentUserId: number;
    currentUserIsSuperAdmin: boolean;
    filters: { q: string; status: string; role: string };
}>();

const roleLabels: Record<string, string> = {
    super_admin: 'Super administrador',
    admin: 'Administrador',
    event_manager: 'Gestor de eventos',
    event_operator: 'Operador de evento',
    production_operator: 'Operador de producción',
    athlete: 'Atleta',
};

function roleLabel(role: string): string {
    return roleLabels[role] ?? role;
}

const assignableRoles = computed(() =>
    currentUserIsSuperAdmin ? roles : roles.filter((r) => r !== 'super_admin'),
);

const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Correo' },
    { key: 'roles', label: 'Roles' },
    { key: 'status', label: 'Estado' },
    { key: 'last_login_at', label: 'Último acceso' },
    { key: 'actions', label: '' },
];

const statusOptions = [
    { value: 'active', label: 'Activo' },
    { value: 'suspended', label: 'Suspendido' },
    { value: 'blocked', label: 'Bloqueado' },
    { value: 'pending', label: 'Pendiente' },
];

const statusBadgeClass: Record<string, string> = {
    active: 'border-emerald-500/30 text-emerald-400',
    suspended: 'border-amber-500/30 text-amber-400',
    blocked: 'border-red-500/30 text-red-400',
    pending: 'border-white/20 text-white/50',
};

function applyFilter(key: 'status' | 'role', value: string) {
    router.get(
        '/admin/users',
        { ...filters, [key]: value.trim() || undefined },
        { preserveState: true, replace: true },
    );
}

function changeStatus(userId: number, status: string) {
    router.patch(
        `/admin/users/${userId}/status`,
        { status },
        {
            preserveScroll: true,
            onError: (errors) => toast.error(errors.status ?? 'No se pudo actualizar el estado.'),
        },
    );
}

const rolesDialogOpen = ref(false);
const rolesDialogUser = ref<UserRow | null>(null);
const selectedRoles = ref<string[]>([]);

function openRolesDialog(row: UserRow) {
    rolesDialogUser.value = row;
    selectedRoles.value = row.roles === '—' ? [] : row.roles.split(', ');
    rolesDialogOpen.value = true;
}

function toggleRole(role: string, checked: boolean) {
    selectedRoles.value = checked
        ? [...selectedRoles.value, role]
        : selectedRoles.value.filter((r) => r !== role);
}

function saveRoles() {
    if (!rolesDialogUser.value) {
return;
}

    router.patch(
        `/admin/users/${rolesDialogUser.value.id}/roles`,
        { roles: selectedRoles.value },
        {
            preserveScroll: true,
            onSuccess: () => (rolesDialogOpen.value = false),
            onError: (errors) => toast.error(errors.roles ?? 'No se pudieron actualizar los roles.'),
        },
    );
}
</script>

<template>
    <Head title="Usuarios" />

    <div class="p-4 md:p-8">
        <h1 class="mb-1 text-xl font-bold text-white">Usuarios</h1>
        <p class="mb-6 text-sm text-white/50">
            Busca, filtra y administra el acceso de cada cuenta. No se
            eliminan usuarios desde aquí — solo se suspende o bloquea el
            acceso.
        </p>

        <div class="mb-4 flex flex-wrap gap-3">
            <Select :model-value="filters.status" @update:model-value="(v) => applyFilter('status', String(v ?? ''))">
                <SelectTrigger class="w-44 border-white/10 bg-fl-graphite/60 text-white">
                    <SelectValue placeholder="Todos los estados" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value=" ">Todos los estados</SelectItem>
                    <SelectItem v-for="s in statusOptions" :key="s.value" :value="s.value">
                        {{ s.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select :model-value="filters.role" @update:model-value="(v) => applyFilter('role', String(v ?? ''))">
                <SelectTrigger class="w-44 border-white/10 bg-fl-graphite/60 text-white">
                    <SelectValue placeholder="Todos los roles" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value=" ">Todos los roles</SelectItem>
                    <SelectItem v-for="r in roles" :key="r" :value="r">
                        {{ roleLabel(r) }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <AdminTable :columns="columns" :rows="users" searchable :initial-query="filters.q">
            <template #cell-roles="{ row }">
                <span>{{ (row.roles as string).split(', ').map(roleLabel).join(', ') }}</span>
            </template>
            <template #cell-status="{ row }">
                <Select
                    :model-value="String(row.status)"
                    :disabled="row.id === currentUserId"
                    @update:model-value="(v) => changeStatus(row.id as number, String(v))"
                >
                    <SelectTrigger
                        class="h-8 w-36 border-white/10 bg-transparent text-xs disabled:opacity-40"
                        :class="statusBadgeClass[row.status as string]"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in statusOptions" :key="s.value" :value="s.value">
                            {{ s.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </template>
            <template #cell-actions="{ row }">
                <Button
                    size="sm"
                    variant="outline"
                    class="border-white/15 text-white hover:bg-white/10 hover:text-white"
                    @click="openRolesDialog(row as unknown as UserRow)"
                >
                    <ShieldCheck class="size-3.5" />
                    Roles
                </Button>
            </template>
        </AdminTable>

        <Dialog v-model:open="rolesDialogOpen">
            <DialogContent class="dark border-white/10 bg-fl-graphite text-white">
                <DialogHeader>
                    <DialogTitle>Roles de {{ rolesDialogUser?.name }}</DialogTitle>
                </DialogHeader>
                <div class="space-y-3">
                    <label
                        v-for="role in assignableRoles"
                        :key="role"
                        class="flex items-center gap-2.5 text-sm text-white/80"
                    >
                        <Checkbox
                            :model-value="selectedRoles.includes(role)"
                            @update:model-value="(v) => toggleRole(role, !!v)"
                        />
                        {{ roleLabel(role) }}
                    </label>
                    <p v-if="!currentUserIsSuperAdmin" class="text-xs text-white/30">
                        Solo un super administrador puede otorgar el rol de super administrador.
                    </p>
                </div>
                <DialogFooter>
                    <Button class="bg-fl-gold text-fl-black hover:bg-fl-gold-soft" @click="saveRoles">
                        Guardar roles
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
