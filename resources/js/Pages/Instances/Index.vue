<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

type InstanceListItem = {
    public_id: string;
    name: string;
    status: string;
    phone_number: string | null;
    last_seen_at: string | null;
    last_error: string | null;
};

const props = defineProps<{
    instances: InstanceListItem[];
    canCreate: boolean;
}>();

const statusLabel: Record<string, string> = {
    creating: 'Criando',
    waiting_qr: 'Aguardando leitura',
    connecting: 'Conectando',
    connected: 'Conectado',
    disconnected: 'Desconectado',
    error: 'Erro',
    deleting: 'Excluindo',
};

function labelFor(status: string): string {
    return statusLabel[status] ?? status;
}
</script>

<template>
    <Head title="Instâncias" />

    <section class="mb-6.5 flex items-end justify-between gap-6">
        <div>
            <h1 class="m-0 text-[34px] leading-[1.08] tracking-tight">Instâncias</h1>
            <p class="mt-2 mb-0 text-[15px] text-muted">
                Conecte o WhatsApp e acompanhe o status da leitura do QR Code.
            </p>
        </div>
        <Link
            v-if="props.canCreate"
            href="/instances/create"
            class="inline-flex h-11 items-center justify-center rounded-[13px] bg-linear-to-br from-green-700 to-green-500 px-4.5 py-3 text-sm font-extrabold text-white shadow-[0_10px_24px_rgba(37,211,102,.22)]"
        >
            + Nova instância
        </Link>
    </section>

    <Card v-if="props.instances.length === 0">
        <CardHeader>
            <div>
                <CardTitle>Nenhuma instância ainda</CardTitle>
                <CardDescription>Crie uma instância para gerar o QR Code e conectar o WhatsApp.</CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <Link
                href="/instances/create"
                class="inline-flex h-11 items-center justify-center rounded-[13px] bg-linear-to-br from-green-700 to-green-500 px-4.5 py-3 text-sm font-extrabold text-white shadow-[0_10px_24px_rgba(37,211,102,.22)]"
            >
                + Nova instância
            </Link>
        </CardContent>
    </Card>

    <div v-else class="flex flex-col gap-4">
        <Link
            v-for="instance in props.instances"
            :key="instance.public_id"
            :href="`/instances/${instance.public_id}`"
            class="block rounded-[18px] border border-line bg-white p-4.5 no-underline shadow-[0_6px_22px_rgba(13,37,29,.03)]"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <strong class="block text-base tracking-tight text-ink">{{ instance.name }}</strong>
                    <span class="mt-1 block text-xs text-muted">{{
                        instance.phone_number ?? 'WhatsApp ainda não vinculado'
                    }}</span>
                </div>
                <span
                    class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1.5 text-xs font-extrabold text-green-800"
                >
                    {{ labelFor(instance.status) }}
                </span>
            </div>
            <p v-if="instance.last_error" class="mt-3 mb-0 text-sm text-destructive">{{ instance.last_error }}</p>
        </Link>
    </div>
</template>
