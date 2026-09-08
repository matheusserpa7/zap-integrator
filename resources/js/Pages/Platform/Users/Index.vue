<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';

type PlatformUser = {
    public_id: string;
    name: string;
    email: string;
    is_platform_admin: boolean;
    disabled: boolean;
    created_at: string | null;
};

defineProps<{
    users: PlatformUser[];
}>();

const page = usePage();
const pendingDelete = ref<PlatformUser | null>(null);

function isCurrentUser(user: PlatformUser): boolean {
    return page.props.auth.user?.public_id === user.public_id;
}

function disableUser(user: PlatformUser): void {
    router.post(`/platform/users/${user.public_id}/disable`);
}

function confirmDelete(): void {
    if (pendingDelete.value === null) {
        return;
    }

    router.delete(`/platform/users/${pendingDelete.value.public_id}`, {
        onFinish: () => {
            pendingDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Usuários" />

    <section class="mb-6.5">
        <h1 class="m-0 text-[34px] leading-[1.08] tracking-tight">Usuários</h1>
        <p class="mt-2 mb-0 text-[15px] text-muted">
            Desative o acesso ou exclua a conta. E-mails na lista de acesso podem se cadastrar de novo após a exclusão.
        </p>
    </section>

    <Card>
        <CardHeader>
            <div>
                <CardTitle>Contas</CardTitle>
                <CardDescription v-if="users.length === 0">Nenhum usuário cadastrado.</CardDescription>
            </div>
        </CardHeader>
        <CardContent v-if="users.length > 0" class="overflow-x-auto pt-0">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-line text-xs tracking-wide text-muted uppercase">
                        <th class="py-3 pr-4 font-extrabold">Nome</th>
                        <th class="py-3 pr-4 font-extrabold">E-mail</th>
                        <th class="py-3 pr-4 font-extrabold">Status</th>
                        <th class="py-3 font-extrabold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.public_id" class="border-b border-line last:border-b-0">
                        <td class="py-3 pr-4 font-semibold">
                            {{ user.name }}
                            <Badge v-if="user.is_platform_admin" class="ml-2">Admin</Badge>
                        </td>
                        <td class="py-3 pr-4">{{ user.email }}</td>
                        <td class="py-3 pr-4">
                            {{ user.disabled ? 'Desativado' : 'Ativo' }}
                        </td>
                        <td class="py-3">
                            <div v-if="!isCurrentUser(user)" class="flex flex-wrap gap-2">
                                <Button
                                    v-if="!user.disabled"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="disableUser(user)"
                                >
                                    Desativar
                                </Button>
                                <Button type="button" variant="destructive" size="sm" @click="pendingDelete = user">
                                    Excluir
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </CardContent>
    </Card>

    <Dialog :open="pendingDelete !== null" @update:open="(open: boolean) => !open && (pendingDelete = null)">
        <DialogContent>
            <DialogTitle>Excluir usuário</DialogTitle>
            <p class="mt-3 mb-6 text-sm text-muted">
                Excluir {{ pendingDelete?.name }} ({{ pendingDelete?.email }})? O e-mail poderá se cadastrar de novo se
                continuar na lista de acesso.
            </p>
            <div class="flex justify-end gap-2">
                <Button type="button" variant="secondary" @click="pendingDelete = null">Cancelar</Button>
                <Button type="button" variant="destructive" @click="confirmDelete">Excluir</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
