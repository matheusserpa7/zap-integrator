<script setup lang="ts">
import ChatShell, { type InboxConversation, type InboxMessage, type InboxSelected } from '@/Pages/Inbox/Index.vue';

defineProps<{
    conversations: InboxConversation[];
    selected: InboxSelected | null;
    messages: InboxMessage[];
    pollIntervalMs: number;
}>();
</script>

<template>
    <ChatShell
        :conversations="conversations"
        :selected="selected"
        :messages="messages"
        :poll-interval-ms="pollIntervalMs"
    />
</template>
