<script setup lang="ts">
import { DialogTitle, type DialogTitleProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<DialogTitleProps & { class?: HTMLAttributes['class'] }>();
</script>

<template>
    <DialogTitle v-bind="props" :class="cn('text-lg font-semibold tracking-tight', props.class)">
        <slot />
    </DialogTitle>
</template>
