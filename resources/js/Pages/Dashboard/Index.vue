<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Inbox, Smartphone, Webhook } from 'lucide-vue-next';

const props = defineProps<{
    instanceCount: number;
    connectedCount: number;
    webhookDeliveredCount: number;
    webhookRetryingCount: number;
    webhookDeadLetterCount: number;
    queueDepth: number | null;
    canCreateInstance: boolean;
    primaryInstanceUrl: string | null;
}>();

const createHref = '/instances/create';
const ctaHref = computed(() => (props.canCreateInstance ? createHref : (props.primaryInstanceUrl ?? '/instances')));

const queueValue = computed(() => (props.queueDepth === null ? '—' : String(props.queueDepth)));
const queueHint = computed(() =>
    props.queueDepth === null ? 'Disponível quando a fila usa Redis' : 'Jobs aguardando nos workers',
);

const stats = computed(() => [
    {
        testId: 'dashboard-instances',
        label: 'Instâncias conectadas',
        value: String(props.connectedCount),
        icon: Smartphone,
        hint: props.instanceCount === 0 ? 'Crie uma instância para começar' : `${props.instanceCount} no workspace`,
    },
    {
        testId: 'dashboard-webhooks',
        label: 'Webhooks entregues',
        value: String(props.webhookDeliveredCount),
        icon: Webhook,
        hint: `${props.webhookRetryingCount} em retry · ${props.webhookDeadLetterCount} dead letter`,
    },
    {
        testId: 'dashboard-queue',
        label: 'Profundidade da fila',
        value: queueValue.value,
        icon: Inbox,
        hint: queueHint.value,
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <section class="mb-6.5 flex items-end justify-between gap-6">
        <div>
            <h1 class="m-0 text-[34px] leading-[1.08] tracking-tight">
                Seu WhatsApp conectado.<br />
                Sem complicação.
            </h1>
            <p class="mt-2 mb-0 text-[15px] text-muted">
                Gerencie sua instância, webhooks e credenciais em um só lugar.
            </p>
        </div>
        <Link
            :href="ctaHref"
            class="inline-flex h-11 items-center justify-center rounded-[13px] bg-linear-to-br from-green-700 to-green-500 px-4.5 py-3 text-sm font-extrabold text-white shadow-[0_10px_24px_rgba(37,211,102,.22)]"
        >
            + Nova instância
        </Link>
    </section>

    <section class="mb-5.5 grid grid-cols-3 gap-4">
        <article
            v-for="stat in stats"
            :key="stat.label"
            :data-testid="stat.testId"
            class="rounded-[18px] border border-line bg-white p-4.5 shadow-[0_6px_22px_rgba(13,37,29,.03)]"
        >
            <div class="mb-4.5 flex items-center justify-between">
                <div class="grid size-9.5 place-items-center rounded-xl bg-green-100 text-green-800">
                    <component :is="stat.icon" class="size-4" />
                </div>
            </div>
            <strong class="block text-[26px] tracking-tight">{{ stat.value }}</strong>
            <span class="block text-xs text-muted">{{ stat.label }}</span>
            <span class="mt-1 block text-[11px] text-muted">{{ stat.hint }}</span>
        </article>
    </section>
</template>
