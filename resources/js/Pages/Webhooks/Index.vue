<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

type EndpointListItem = {
    public_id: string;
    name: string;
    url: string;
    event_type: string;
    payload_mode: string;
    enabled: boolean;
    failure_count: number;
};

defineProps<{
    endpoints: EndpointListItem[];
    canCreate: boolean;
}>();
</script>

<template>
    <Head title="Webhooks" />

    <section class="mb-6.5 flex items-end justify-between gap-6">
        <div>
            <h1 class="m-0 text-[34px] leading-[1.08] tracking-tight">Webhooks</h1>
            <p class="mt-2 mb-0 text-[15px] text-muted">
                Um endpoint por evento. O payload padrão é o JSON canônico da ZAP; personalize se precisar.
            </p>
        </div>
        <Link
            v-if="canCreate"
            href="/webhooks/builder"
            class="inline-flex h-11 items-center justify-center rounded-[13px] bg-linear-to-br from-green-700 to-green-500 px-4.5 py-3 text-sm font-extrabold text-white shadow-[0_10px_24px_rgba(37,211,102,.22)]"
        >
            + Novo webhook
        </Link>
    </section>

    <Card v-if="endpoints.length === 0">
        <CardHeader>
            <div>
                <CardTitle>Nenhum webhook ainda</CardTitle>
                <CardDescription>
                    Crie um endpoint, teste com um fixture e salve sem depender do resultado do teste.
                </CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <Link
                v-if="canCreate"
                href="/webhooks/builder"
                class="inline-flex h-11 items-center justify-center rounded-[13px] bg-linear-to-br from-green-700 to-green-500 px-4.5 py-3 text-sm font-extrabold text-white shadow-[0_10px_24px_rgba(37,211,102,.22)]"
            >
                + Novo webhook
            </Link>
        </CardContent>
    </Card>

    <div v-else class="flex flex-col gap-4">
        <Link
            v-for="endpoint in endpoints"
            :key="endpoint.public_id"
            :href="`/webhooks/${endpoint.public_id}`"
            class="block rounded-[18px] border border-line bg-white p-4.5 no-underline shadow-[0_6px_22px_rgba(13,37,29,.03)]"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <strong class="block text-base tracking-tight text-ink">{{ endpoint.name }}</strong>
                    <span class="mt-1 block font-mono text-xs text-muted">{{ endpoint.url }}</span>
                </div>
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-1.5 text-[11px] font-black"
                    :class="endpoint.enabled ? 'bg-green-100 text-green-800' : 'bg-[#F3F6F5] text-muted'"
                >
                    {{ endpoint.enabled ? 'Ativo' : 'Pausado' }}
                </span>
            </div>
            <p class="mt-3 mb-0 text-[13px] text-muted">
                <code>{{ endpoint.event_type }}</code>
                · {{ endpoint.payload_mode === 'canonical' ? 'JSON canônico' : 'Mapa personalizado' }}
                <template v-if="endpoint.failure_count > 0"> · {{ endpoint.failure_count }} falhas</template>
            </p>
        </Link>
    </div>
</template>
