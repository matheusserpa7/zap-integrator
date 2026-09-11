<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { useInboxRealtime } from '@/composables/useInboxRealtime';

export type InboxContact = {
    public_id: string;
    display_name: string;
    wa_id: string;
    initials: string;
};

export type InboxConversation = {
    public_id: string;
    contact: InboxContact;
    preview: string | null;
    last_message_at: string | null;
};

export type InboxMessage = {
    public_id: string;
    direction: 'inbound' | 'outbound';
    type: string;
    status: string;
    body: string;
    occurred_at: string;
    media_expired: boolean;
};

export type InboxSelected = InboxConversation & {
    instance_public_id?: string | null;
};

const props = defineProps<{
    conversations: InboxConversation[];
    selected: InboxSelected | null;
    messages: InboxMessage[];
    pollIntervalMs: number;
}>();

const query = ref('');

const { start, stop } = usePoll(
    props.pollIntervalMs || 4000,
    { only: ['conversations', 'selected', 'messages'] },
    { autoStart: false },
);
const live = useInboxRealtime({ start, stop });

watch(
    () => props.pollIntervalMs,
    () => {
        if (live.value) {
            return;
        }

        stop();
        start();
    },
);

const filteredConversations = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (term === '') {
        return props.conversations;
    }

    return props.conversations.filter((conversation) => {
        const haystack = `${conversation.contact.display_name} ${conversation.contact.wa_id}`.toLowerCase();

        return haystack.includes(term);
    });
});

const statusLabel: Record<string, string> = {
    sending: 'Enviando',
    sent: 'Enviada',
    failed: 'Falhou',
    received: 'Recebida',
    accepted: 'Aceita',
};

function formatTime(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    const now = new Date();
    const sameDay =
        date.getFullYear() === now.getFullYear() &&
        date.getMonth() === now.getMonth() &&
        date.getDate() === now.getDate();

    if (sameDay) {
        return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);

    if (
        date.getFullYear() === yesterday.getFullYear() &&
        date.getMonth() === yesterday.getMonth() &&
        date.getDate() === yesterday.getDate()
    ) {
        return 'Ontem';
    }

    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
}

function formatPhone(waId: string): string {
    return waId.startsWith('+') ? waId : `+${waId}`;
}

function isActive(conversation: InboxConversation): boolean {
    return props.selected?.public_id === conversation.public_id;
}
</script>

<template>
    <Head title="Conversas" />

    <section class="overflow-hidden rounded-lg border border-line bg-white shadow-[0_8px_30px_rgba(13,37,29,.035)]">
        <div class="flex items-start justify-between gap-4 px-5.5 pt-5">
            <div>
                <h1 class="m-0 text-lg tracking-tight">Conversas</h1>
                <p class="mt-1 mb-0 text-[13px] text-muted">
                    Inbox somente leitura: lista à esquerda e thread à direita. O envio é feito pela API.
                </p>
            </div>
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1.5 text-xs font-extrabold text-green-800"
                data-testid="inbox-realtime"
                :data-live="live ? 'true' : 'false'"
            >
                Tempo real
            </span>
        </div>

        <div class="mt-5 grid min-h-[620px] grid-cols-[320px_minmax(0,1fr)] border-t border-line">
            <aside class="flex min-w-0 flex-col border-r border-line bg-[#FBFDFC]">
                <div class="flex items-center justify-between gap-3 border-b border-line p-4.5">
                    <div>
                        <h2 class="m-0 text-base tracking-tight">Inbox</h2>
                        <span class="text-xs text-muted">
                            {{ conversations.length === 1 ? '1 conversa' : `${conversations.length} conversas` }}
                        </span>
                    </div>
                    <Badge variant="success">{{ conversations.length }}</Badge>
                </div>
                <div class="border-b border-[#F0F3F1] px-4.5 py-4">
                    <Input v-model="query" placeholder="Buscar contato ou número..." />
                </div>
                <div class="flex-1 space-y-1 overflow-auto p-2">
                    <p v-if="filteredConversations.length === 0" class="px-3 py-8 text-center text-sm text-muted">
                        Nenhuma conversa ainda.
                    </p>
                    <Link
                        v-for="conversation in filteredConversations"
                        :key="conversation.public_id"
                        :href="`/inbox/${conversation.public_id}`"
                        data-testid="inbox-conversation"
                        :class="[
                            'grid grid-cols-[46px_minmax(0,1fr)_auto] items-center gap-3 rounded-[14px] px-2.5 py-3',
                            isActive(conversation) &&
                                'bg-white shadow-[inset_0_0_0_1px_rgba(37,211,102,.18),0_10px_20px_rgba(13,37,29,.03)]',
                        ]"
                    >
                        <div
                            class="grid size-[46px] place-items-center rounded-[14px] bg-linear-to-br from-green-700 to-green-500 text-sm font-black text-white"
                        >
                            {{ conversation.contact.initials }}
                        </div>
                        <div class="min-w-0">
                            <div class="mb-1 flex items-center justify-between gap-2.5">
                                <strong class="truncate text-sm">{{ conversation.contact.display_name }}</strong>
                                <small class="text-[11px] text-muted">{{
                                    formatTime(conversation.last_message_at)
                                }}</small>
                            </div>
                            <div class="truncate text-xs text-muted">{{ conversation.preview }}</div>
                        </div>
                    </Link>
                </div>
            </aside>

            <section class="flex min-w-0 flex-col">
                <template v-if="selected">
                    <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="grid size-[46px] place-items-center rounded-[14px] bg-linear-to-br from-green-700 to-green-500 text-sm font-black text-white"
                            >
                                {{ selected.contact.initials }}
                            </div>
                            <div>
                                <strong class="block">{{ selected.contact.display_name }}</strong>
                                <span class="text-xs text-muted">{{ formatPhone(selected.contact.wa_id) }}</span>
                            </div>
                        </div>
                    </header>
                    <div class="flex-1 space-y-3 overflow-auto bg-[#FBFDFC] p-5">
                        <div
                            v-for="message in messages"
                            :key="message.public_id"
                            :class="['flex', message.direction === 'outbound' ? 'justify-end' : 'justify-start']"
                        >
                            <div
                                :class="[
                                    'max-w-[72%] rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed',
                                    message.direction === 'outbound'
                                        ? 'bg-green-100 text-green-900'
                                        : 'bg-white text-ink shadow-[0_8px_20px_rgba(13,37,29,.04)]',
                                ]"
                            >
                                <p class="m-0">{{ message.body }}</p>
                                <p v-if="message.media_expired" class="m-0 mt-1 text-[11px] text-muted">
                                    Mídia expirada
                                </p>
                                <div class="mt-1 flex items-center justify-end gap-1.5 text-[11px] text-muted">
                                    <span>{{ formatTime(message.occurred_at) }}</span>
                                    <span v-if="message.direction === 'outbound'">{{
                                        statusLabel[message.status] ?? message.status
                                    }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="grid flex-1 place-items-center bg-[#FBFDFC] p-8 text-center">
                    <div>
                        <strong class="block text-base">Selecione uma conversa</strong>
                        <p class="mt-1 mb-0 text-sm text-muted">
                            O histórico 1:1 aparece aqui. Não há compositor de envio.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </section>
</template>
