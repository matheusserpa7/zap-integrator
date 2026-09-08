<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import type { SharedProps } from '@/Types';

type MappingRow = {
    path: string;
    value_mode: 'field' | 'fixed' | 'expression';
    value: string;
};

type HeaderRow = {
    name: string;
    value_mode: 'field' | 'fixed' | 'expression';
    value: string;
};

type CatalogEntry = {
    paths: string[];
    aliases: Record<string, string>;
    chips: { path: string; type: string }[];
    fixture: Record<string, unknown>;
    leaves: MappingRow[];
};

type EndpointProp = {
    public_id: string;
    name: string;
    url: string;
    description: string | null;
    event_type: string;
    payload_mode: 'canonical' | 'custom';
    body_mapping: MappingRow[];
    headers: HeaderRow[];
    enabled: boolean;
} | null;

type DeliveryProp = {
    public_id: string;
    event_id: string;
    attempt: number;
    status: string;
    status_label: string;
    http_status: number | null;
    response_excerpt: string | null;
    duration_ms: number | null;
    created_at: string | null;
};

const props = defineProps<{
    endpoint: EndpointProp;
    deliveries: DeliveryProp[];
    canEdit: boolean;
    eventOptions: { value: string; label: string }[];
    catalog: Record<string, CatalogEntry>;
    defaultEventType: string;
}>();

const page = usePage<SharedProps>();
const secretOpen = ref(false);
const webhookSecret = ref('');
const copied = ref(false);
const previewTab = ref<'json' | 'headers'>('json');
const testResult = ref('Nenhum teste executado ainda.');
const testing = ref(false);

const expressionExample = 'zap-{{ message.id }}';

watch(
    () => page.props.flash.webhookSecret,
    (secret) => {
        if (typeof secret === 'string' && secret !== '') {
            webhookSecret.value = secret;
            copied.value = false;
            secretOpen.value = true;
        }
    },
    { immediate: true },
);

const form = useForm({
    name: props.endpoint?.name ?? 'Webhook de mensagem',
    url: props.endpoint?.url ?? '',
    description: props.endpoint?.description ?? '',
    event_type: props.endpoint?.event_type ?? props.defaultEventType,
    payload_mode: props.endpoint?.payload_mode ?? 'canonical',
    enabled: props.endpoint?.enabled ?? true,
    body_mapping: props.endpoint?.body_mapping ?? [],
    headers: props.endpoint?.headers ?? [],
    rotate_secret: false,
});

const currentCatalog = computed(() => props.catalog[form.event_type] ?? props.catalog[props.defaultEventType]);

function csrfToken(): string {
    const cookie = globalThis.document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : '';
}

function getByPath(source: unknown, path: string): unknown {
    return path.split('.').reduce<unknown>((current, key) => {
        if (current !== null && typeof current === 'object' && key in current) {
            return (current as Record<string, unknown>)[key];
        }

        return null;
    }, source);
}

function setByPath(target: Record<string, unknown>, path: string, value: unknown): void {
    const parts = path.split('.');
    let cursor: Record<string, unknown> = target;

    parts.forEach((part, index) => {
        if (index === parts.length - 1) {
            cursor[part] = value;

            return;
        }

        const next = cursor[part];

        if (next === null || typeof next !== 'object' || Array.isArray(next)) {
            cursor[part] = {};
        }

        cursor = cursor[part] as Record<string, unknown>;
    });
}

function resolveField(path: string): unknown {
    const aliases = currentCatalog.value?.aliases ?? {};
    const canonical = aliases[path] ?? path;

    return getByPath(currentCatalog.value?.fixture ?? {}, canonical);
}

function resolveRow(row: MappingRow | HeaderRow): unknown {
    if (row.value_mode === 'fixed') {
        return row.value;
    }

    if (row.value_mode === 'field') {
        return resolveField(row.value);
    }

    return row.value.replace(/\{\{\s*([A-Za-z][A-Za-z0-9_.]*)\s*\}\}/g, (_match, path: string) => {
        const resolved = resolveField(path);

        return resolved === null || resolved === undefined ? '' : String(resolved);
    });
}

const previewBody = computed(() => {
    if (form.payload_mode === 'canonical') {
        return currentCatalog.value?.fixture ?? {};
    }

    const output: Record<string, unknown> = {};

    form.body_mapping.forEach((row) => {
        if (row.path !== '') {
            setByPath(output, row.path, resolveRow(row));
        }
    });

    return output;
});

const previewJson = computed(() => JSON.stringify(previewBody.value, null, 2));

const previewHeaders = computed(() => {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'User-Agent': 'ZAP-Webhooks/1.0',
        'X-ZAP-Event-Id': String((currentCatalog.value?.fixture as { id?: string })?.id ?? 'evt_…'),
        'X-ZAP-Timestamp': '<unix>',
        'X-ZAP-Signature': 'sha256=••••••••',
    };

    form.headers.forEach((row) => {
        if (row.name !== '') {
            headers[row.name] = String(resolveRow(row) ?? '');
        }
    });

    return headers;
});

const isCustom = computed(() => form.payload_mode === 'custom');
const chips = computed(() => currentCatalog.value?.chips ?? []);

function personalize(): void {
    form.payload_mode = 'custom';
    form.body_mapping = (currentCatalog.value?.leaves ?? []).map((row) => ({ ...row }));
}

function restoreCanonical(): void {
    form.payload_mode = 'canonical';
    form.body_mapping = [];
}

function addRow(): void {
    form.body_mapping.push({ path: '', value_mode: 'field', value: '' });
}

function removeRow(index: number): void {
    form.body_mapping.splice(index, 1);
}

function addHeader(): void {
    form.headers.push({ name: 'Authorization', value_mode: 'fixed', value: '' });
}

function removeHeader(index: number): void {
    form.headers.splice(index, 1);
}

function save(): void {
    if (!props.canEdit) {
        return;
    }

    if (props.endpoint) {
        form.patch(`/webhooks/${props.endpoint.public_id}`, { preserveScroll: true });

        return;
    }

    form.post('/webhooks', { preserveScroll: true });
}

async function testWebhook(): Promise<void> {
    const url = props.endpoint ? `/webhooks/${props.endpoint.public_id}/test` : '/webhooks/test';
    testing.value = true;

    try {
        const response = await globalThis.fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ ...form.data(), rotate_secret: false }),
        });
        const data = (await response.json()) as {
            ok?: boolean;
            http_status?: number | null;
            duration_ms?: number;
            excerpt?: string;
            message?: string;
        };

        if (!response.ok) {
            testResult.value = data.message ?? 'O teste não foi enviado. Verifique a URL, o mapa e os cabeçalhos.';

            return;
        }

        const status = data.http_status ?? '—';
        const latency = data.duration_ms ?? 0;
        const excerpt = data.excerpt ? data.excerpt.slice(0, 180) : 'sem corpo';
        testResult.value = `${data.ok ? 'Sucesso' : 'Falha'} · HTTP ${status} · ${latency} ms · ${excerpt}`;
    } catch {
        testResult.value = 'O teste não foi enviado. Verifique a URL, o mapa e os cabeçalhos.';
    } finally {
        testing.value = false;
    }
}

function duplicate(): void {
    if (!props.endpoint) {
        return;
    }

    router.post(`/webhooks/${props.endpoint.public_id}/duplicate`);
}

function retryDelivery(publicId: string): void {
    router.post(`/webhooks/deliveries/${publicId}/retry`, {}, { preserveScroll: true });
}

async function copySecret(): Promise<void> {
    await globalThis.navigator.clipboard.writeText(webhookSecret.value);
    copied.value = true;
}

function insertChip(path: string): void {
    if (!isCustom.value || !props.canEdit) {
        return;
    }

    form.body_mapping.push({ path: path.replaceAll('.', '_'), value_mode: 'field', value: path });
}
</script>

<template>
    <Head title="Webhook Builder" />

    <p class="mb-3 text-[13px]">
        <Link href="/webhooks" class="text-muted no-underline hover:text-ink">Webhooks</Link>
        <span class="text-muted"> / </span>
        <span>{{ props.endpoint ? props.endpoint.name : 'Novo webhook' }}</span>
    </p>

    <section class="overflow-hidden rounded-lg border border-line bg-white shadow-[0_8px_30px_rgba(13,37,29,.035)]">
        <div class="flex items-start justify-between gap-4 px-5.5 pt-5 pb-5">
            <div>
                <h1 class="m-0 text-lg tracking-tight">Webhook Builder</h1>
                <p class="mt-1 mb-0 text-[13px] text-muted">
                    Monte o payload que seu sistema vai receber sem precisar transformar o JSON manualmente.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-[11px] font-black"
                    :class="form.enabled ? 'bg-green-100 text-green-800' : 'bg-[#F3F6F5] text-muted'"
                    :disabled="!props.canEdit"
                    @click="form.enabled = !form.enabled"
                >
                    {{ form.enabled ? 'Ativo' : 'Pausado' }}
                </button>
                <Button variant="secondary" size="sm" :disabled="!props.endpoint || !props.canEdit" @click="duplicate">
                    Duplicar
                </Button>
                <Button
                    size="sm"
                    data-testid="webhook-save"
                    :disabled="!props.canEdit || form.processing"
                    @click="save"
                >
                    {{ form.processing ? 'Salvando…' : 'Salvar webhook' }}
                </Button>
            </div>
        </div>
        <p
            v-if="form.errors.name || form.errors.url || form.errors.event_type || form.errors.body_mapping"
            class="px-5.5 pb-3 text-sm text-destructive"
        >
            {{ form.errors.name || form.errors.url || form.errors.event_type || form.errors.body_mapping }}
        </p>

        <div class="builder-grid grid min-h-[610px] grid-cols-[260px_minmax(420px,1fr)_340px] border-t border-line">
            <aside class="min-w-0 bg-[#FBFDFC] p-5">
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="grid size-8 place-items-center rounded-lg bg-green-100 text-[11px] font-black text-green-800"
                    >
                        01
                    </div>
                    <div>
                        <strong class="block text-sm">Escolha o gatilho</strong>
                        <span class="text-xs text-muted">Quando o fluxo deve executar</span>
                    </div>
                </div>
                <div class="mb-4 rounded-xl border border-line bg-white p-4">
                    <label class="mb-1.5 block text-xs font-extrabold" for="builder-name">Nome</label>
                    <Input id="builder-name" v-model="form.name" class="mb-3" :disabled="!props.canEdit" />
                    <div class="mb-3">
                        <strong class="block text-sm">WhatsApp Event</strong>
                        <div class="text-xs text-muted">Um evento por endpoint</div>
                    </div>
                    <label class="mb-1.5 block text-xs font-extrabold" for="builder-event">Evento normalizado</label>
                    <Select
                        :model-value="form.event_type"
                        :disabled="!props.canEdit"
                        @update:model-value="form.event_type = String($event)"
                    >
                        <SelectTrigger id="builder-event">
                            <SelectValue placeholder="Selecione o evento" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="option in props.eventOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="mb-4 rounded-xl border border-line bg-white p-4">
                    <strong class="block text-sm">HTTP Request</strong>
                    <div class="mb-3 text-xs text-muted">Entrega para seu sistema</div>
                    <label class="mb-1.5 block text-xs font-extrabold" for="builder-url">Endpoint</label>
                    <Input
                        id="builder-url"
                        v-model="form.url"
                        placeholder="https://crm.minhaempresa.com/webhooks/zap"
                        :disabled="!props.canEdit"
                    />
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-extrabold">Método</label>
                            <Input model-value="POST" disabled />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-extrabold">Formato</label>
                            <Input model-value="JSON" disabled />
                        </div>
                    </div>
                    <p v-if="form.errors.url" class="mt-2 mb-0 text-xs text-destructive">{{ form.errors.url }}</p>
                    <div class="mt-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-extrabold">Cabeçalhos extras</span>
                            <button
                                type="button"
                                class="text-[11px] font-bold text-green-800"
                                :disabled="!props.canEdit"
                                @click="addHeader"
                            >
                                + Adicionar
                            </button>
                        </div>
                        <div
                            v-for="(header, index) in form.headers"
                            :key="`h-${index}`"
                            class="mb-2 grid grid-cols-[1fr_auto] gap-1"
                        >
                            <Input v-model="header.name" placeholder="Authorization" :disabled="!props.canEdit" />
                            <button
                                type="button"
                                class="px-2 text-muted"
                                :disabled="!props.canEdit"
                                @click="removeHeader(index)"
                            >
                                ×
                            </button>
                            <Input
                                v-model="header.value"
                                class="col-span-2"
                                placeholder="Bearer …"
                                :disabled="!props.canEdit"
                            />
                        </div>
                        <p v-if="form.errors.headers" class="mt-1 mb-0 text-xs text-destructive">
                            {{ form.errors.headers }}
                        </p>
                    </div>
                </div>
                <div>
                    <div class="mb-2 flex justify-between text-[11px] font-bold text-muted">
                        <span>Dados disponíveis</span>
                        <span>{{ isCustom ? 'clique para usar' : 'somente leitura' }}</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="chip in chips"
                            :key="chip.path"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-full border border-line bg-white px-2 py-1 text-[11px]"
                            @click="insertChip(chip.path)"
                        >
                            <code>{{ chip.path }}</code>
                            <span class="text-muted">{{ chip.type }}</span>
                        </button>
                    </div>
                </div>
            </aside>

            <section class="min-w-0 border-x border-line p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="grid size-8 place-items-center rounded-lg bg-green-100 text-[11px] font-black text-green-800"
                        >
                            02
                        </div>
                        <div>
                            <strong class="block text-sm">Monte o payload</strong>
                            <span class="text-xs text-muted"
                                >Defina o nome de cada parâmetro e de onde vem o valor</span
                            >
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="!isCustom"
                            variant="secondary"
                            size="sm"
                            :disabled="!props.canEdit"
                            @click="personalize"
                        >
                            Personalizar
                        </Button>
                        <Button
                            v-else
                            variant="secondary"
                            size="sm"
                            :disabled="!props.canEdit"
                            @click="restoreCanonical"
                        >
                            Restaurar padrão
                        </Button>
                    </div>
                </div>

                <div
                    v-if="!isCustom"
                    class="rounded-xl border border-dashed border-line bg-[#FBFDFC] p-4 text-sm text-muted"
                >
                    O payload canônico da ZAP está bloqueado. Clique em <b>Personalizar</b> para copiar os campos e
                    montar o seu mapa.
                </div>

                <template v-else>
                    <div class="mb-2 grid grid-cols-[1fr_1fr_1fr_auto] gap-2 text-[11px] font-bold text-muted">
                        <span>Parâmetro</span>
                        <span>Tipo do valor</span>
                        <span>Valor</span>
                        <span />
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(row, index) in form.body_mapping"
                            :key="`m-${index}`"
                            class="grid grid-cols-[1fr_1fr_1fr_auto] items-center gap-2"
                        >
                            <Input v-model="row.path" :disabled="!props.canEdit" placeholder="customer.name" />
                            <Select
                                :model-value="row.value_mode"
                                :disabled="!props.canEdit"
                                @update:model-value="row.value_mode = $event as MappingRow['value_mode']"
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="field">Campo do evento</SelectItem>
                                    <SelectItem value="fixed">Valor fixo</SelectItem>
                                    <SelectItem value="expression">Expressão</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input v-model="row.value" :disabled="!props.canEdit" />
                            <button
                                type="button"
                                class="px-2 text-muted"
                                :disabled="!props.canEdit"
                                @click="removeRow(index)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <div class="builder-actions mt-4 flex flex-wrap items-center gap-3">
                        <Button variant="secondary" size="sm" :disabled="!props.canEdit" @click="addRow">
                            + Adicionar parâmetro
                        </Button>
                        <span class="text-xs text-muted"
                            >Use nomes com ponto para criar objetos: <b>customer.name</b></span
                        >
                    </div>
                    <div class="mt-4 rounded-xl bg-[#F3FBF7] p-3 text-xs text-ink">
                        <b>3 formas de preencher um valor:</b><br />
                        <b>Fixo</b> — texto digitado por você, como <code>lead_whatsapp</code>.<br />
                        <b>Campo do evento</b> — dado canônico allowlistado, como <code>message.text</code>.<br />
                        <b>Expressão</b> — mistura texto e variáveis, como <code>{{ expressionExample }}</code
                        >.
                    </div>
                </template>
            </section>

            <aside class="min-w-0 bg-[#FBFDFC] p-5">
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="grid size-8 place-items-center rounded-lg bg-green-100 text-[11px] font-black text-green-800"
                    >
                        03
                    </div>
                    <div>
                        <strong class="block text-sm">Preview & Test</strong>
                        <span class="text-xs text-muted">Veja exatamente o que será enviado</span>
                    </div>
                </div>
                <div class="mb-3 flex gap-2">
                    <button
                        type="button"
                        class="rounded-full px-3 py-1 text-[11px] font-bold"
                        :class="previewTab === 'json' ? 'bg-green-100 text-green-800' : 'text-muted'"
                        @click="previewTab = 'json'"
                    >
                        JSON Body
                    </button>
                    <button
                        type="button"
                        class="rounded-full px-3 py-1 text-[11px] font-bold"
                        :class="previewTab === 'headers' ? 'bg-green-100 text-green-800' : 'text-muted'"
                        @click="previewTab = 'headers'"
                    >
                        Headers
                    </button>
                </div>
                <pre
                    data-testid="webhook-preview"
                    class="max-h-72 overflow-auto rounded-xl bg-[#0D1F19] p-4 text-[12px] leading-relaxed text-[#D8FCE4]"
                    >{{ previewTab === 'json' ? previewJson : JSON.stringify(previewHeaders, null, 2) }}</pre>
                <div class="mt-3 space-y-2 text-xs">
                    <div class="flex justify-between gap-2">
                        <span class="text-muted">Destino</span>
                        <code class="truncate">{{ form.url || '—' }}</code>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-muted">Assinatura</span>
                        <code>X-ZAP-Signature: sha256=••••••••</code>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-muted">Evento</span>
                        <code>{{ form.event_type }}</code>
                    </div>
                </div>
                <Button class="mt-3.5 w-full" :disabled="!props.canEdit || testing" @click="testWebhook">
                    {{ testing ? 'Testando…' : '▶ Testar webhook' }}
                </Button>
                <p class="mt-2 text-xs text-muted">{{ testResult }}</p>
            </aside>
        </div>
    </section>

    <section v-if="props.endpoint" class="mt-6 rounded-lg border border-line bg-white p-5">
        <h2 class="m-0 text-base tracking-tight">Histórico de entregas</h2>
        <p v-if="props.deliveries.length === 0" class="mt-2 mb-0 text-sm text-muted">Nenhuma entrega ainda.</p>
        <ul v-else class="mt-3 mb-0 list-none space-y-2 p-0">
            <li
                v-for="delivery in props.deliveries"
                :key="delivery.public_id"
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line px-3 py-3 text-sm"
            >
                <div>
                    <strong data-testid="webhook-delivery-status">{{ delivery.status_label }}</strong>
                    <span class="text-muted"> · {{ delivery.event_id }} · tentativa {{ delivery.attempt }}</span>
                    <div class="text-xs text-muted">
                        HTTP {{ delivery.http_status ?? '—' }}
                        <template v-if="delivery.duration_ms"> · {{ delivery.duration_ms }} ms</template>
                        <template v-if="delivery.response_excerpt"> · {{ delivery.response_excerpt }}</template>
                    </div>
                </div>
                <Button
                    v-if="delivery.status !== 'delivered' && props.canEdit"
                    variant="secondary"
                    size="sm"
                    @click="retryDelivery(delivery.public_id)"
                >
                    Tentar de novo
                </Button>
            </li>
        </ul>
    </section>

    <Dialog v-model:open="secretOpen">
        <DialogContent>
            <DialogTitle>Copie o segredo agora</DialogTitle>
            <p class="mt-1 mb-4 text-sm text-muted">
                Use este valor para validar o HMAC. Ele não será exibido novamente.
            </p>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-[#0E201A] px-3.5 py-3.5 text-[#D8FCE4]">
                <span class="break-all font-mono text-sm">{{ webhookSecret }}</span>
                <Button size="sm" variant="outline" @click="copySecret">
                    {{ copied ? 'Copiado' : 'Copiar' }}
                </Button>
            </div>
            <Button class="mt-4" variant="outline" @click="secretOpen = false">Entendi</Button>
        </DialogContent>
    </Dialog>
</template>
