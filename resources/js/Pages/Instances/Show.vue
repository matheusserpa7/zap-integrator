<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Form, Head, router, usePoll } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';

type InstanceShow = {
    public_id: string;
    name: string;
    status: string;
    phone_number: string | null;
    qr_code: string | null;
    qr_expires_at: string | null;
    connected_at: string | null;
    last_seen_at: string | null;
    last_error: string | null;
};

const props = defineProps<{
    instance: InstanceShow;
    canRefreshQr: boolean;
    canDisconnect: boolean;
    canDelete: boolean;
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

const pollingStatuses = new Set(['creating', 'waiting_qr', 'connecting', 'deleting']);
const shouldPoll = computed(() => pollingStatuses.has(props.instance.status));
const { start, stop } = usePoll(
    3000,
    { only: ['instance', 'canRefreshQr', 'canDisconnect', 'canDelete'] },
    { autoStart: false },
);
const qrCaption = ref('');
const confirmDelete = ref(false);

function labelFor(status: string): string {
    return statusLabel[status] ?? status;
}

function formatTime(value: string | null): string {
    if (!value) {
        return 'Ainda não sincronizado';
    }

    return new Date(value).toLocaleString('pt-BR');
}

function tickQrCaption(): void {
    if (!props.instance.qr_expires_at || !props.instance.qr_code) {
        qrCaption.value =
            props.instance.status === 'waiting_qr' ? 'QR Code indisponível. Atualize para gerar outro.' : '';

        return;
    }

    const remainingMs = new Date(props.instance.qr_expires_at).getTime() - Date.now();

    if (remainingMs <= 0) {
        qrCaption.value = 'QR Code expirado';

        return;
    }

    const seconds = Math.floor(remainingMs / 1000);
    const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
    const rest = String(seconds % 60).padStart(2, '0');
    qrCaption.value = `QR Code expira em ${minutes}:${rest}`;
}

function syncPolling(): void {
    if (shouldPoll.value) {
        start();
    } else {
        stop();
    }
}

useIntervalFn(tickQrCaption, 1000, { immediate: true });
watch(shouldPoll, syncPolling, { immediate: true });
watch(() => props.instance.qr_code, tickQrCaption);
watch(() => props.instance.qr_expires_at, tickQrCaption);

function submitDelete(): void {
    router.delete(`/instances/${props.instance.public_id}`);
}
</script>

<template>
    <Head :title="instance.name" />

    <Card>
        <CardHeader>
            <div>
                <CardTitle>Conectar WhatsApp</CardTitle>
                <CardDescription>Leia o QR Code com o WhatsApp para ativar sua instância.</CardDescription>
            </div>
            <span
                data-testid="instance-status"
                class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1.5 text-xs font-extrabold whitespace-nowrap text-green-800"
            >
                {{ labelFor(instance.status) }}
            </span>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-[190px_1fr] items-center gap-6">
                <div class="rounded-[18px] border border-dashed border-[#CAD7D1] bg-[#FBFDFC] p-4 text-center">
                    <img
                        v-if="instance.qr_code"
                        :src="instance.qr_code"
                        alt="QR Code do WhatsApp"
                        class="mx-auto size-[158px] rounded-[10px] bg-white object-contain p-2 shadow-[inset_0_0_0_1px_#edf1ef]"
                    />
                    <div
                        v-else
                        class="mx-auto grid aspect-square size-[158px] place-items-center rounded-[10px] bg-white p-2 text-center text-[11px] text-muted shadow-[inset_0_0_0_1px_#edf1ef]"
                    >
                        <span v-if="instance.status === 'creating'">Gerando QR Code…</span>
                        <span v-else-if="instance.status === 'connected'">Instância conectada</span>
                        <span v-else-if="instance.status === 'error'">Falha ao gerar o QR Code</span>
                        <span v-else>QR Code indisponível</span>
                    </div>
                    <div v-if="qrCaption" class="mt-2.5 text-[11px] text-muted">{{ qrCaption }}</div>
                </div>
                <div class="flex flex-col gap-3.5">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-line bg-[#FBFDFC] px-3.5 py-3.5">
                            <label
                                class="mb-1 block text-[10px] font-extrabold tracking-wider text-[#91A098] uppercase"
                            >
                                Instância
                            </label>
                            <strong class="text-[13px]">{{ instance.name }}</strong>
                        </div>
                        <div class="rounded-xl border border-line bg-[#FBFDFC] px-3.5 py-3.5">
                            <label
                                class="mb-1 block text-[10px] font-extrabold tracking-wider text-[#91A098] uppercase"
                            >
                                Status
                            </label>
                            <strong class="text-[13px] text-green-700">{{ labelFor(instance.status) }}</strong>
                        </div>
                    </div>
                    <div class="rounded-xl border border-line bg-[#FBFDFC] px-3.5 py-3.5">
                        <label class="mb-1 block text-[10px] font-extrabold tracking-wider text-[#91A098] uppercase">
                            Número
                        </label>
                        <strong class="text-[13px]">{{ instance.phone_number ?? 'Aguardando conexão' }}</strong>
                    </div>
                    <div class="rounded-xl border border-line bg-[#FBFDFC] px-3.5 py-3.5">
                        <label class="mb-1 block text-[10px] font-extrabold tracking-wider text-[#91A098] uppercase">
                            Última sincronização
                        </label>
                        <strong class="text-[13px]">{{ formatTime(instance.last_seen_at) }}</strong>
                    </div>
                    <p v-if="instance.last_error" class="m-0 text-sm text-destructive">{{ instance.last_error }}</p>
                    <div class="flex flex-wrap gap-2">
                        <Form
                            v-if="canRefreshQr"
                            v-slot="{ processing }"
                            :action="`/instances/${instance.public_id}/qr`"
                            method="post"
                            class="contents"
                        >
                            <Button type="submit" variant="outline" :disabled="processing">
                                {{ processing ? 'Atualizando…' : 'Atualizar QR Code' }}
                            </Button>
                        </Form>
                        <Form
                            v-if="canDisconnect"
                            v-slot="{ processing }"
                            :action="`/instances/${instance.public_id}/disconnect`"
                            method="post"
                            class="contents"
                        >
                            <Button type="submit" variant="secondary" :disabled="processing">
                                {{ processing ? 'Desconectando…' : 'Desconectar' }}
                            </Button>
                        </Form>
                        <Button v-if="canDelete" type="button" variant="destructive" @click="confirmDelete = true">
                            Excluir
                        </Button>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>

    <Dialog :open="confirmDelete" @update:open="confirmDelete = $event">
        <DialogContent>
            <DialogTitle>Excluir instância</DialogTitle>
            <p class="mt-2 text-sm text-muted">
                A instância {{ instance.name }} será removida. Esta ação não pode ser desfeita.
            </p>
            <div class="mt-5 flex justify-end gap-2">
                <Button type="button" variant="secondary" @click="confirmDelete = false">Cancelar</Button>
                <Button type="button" variant="destructive" @click="submitDelete">Excluir</Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
