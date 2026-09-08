<script setup lang="ts">
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div :class="cn('flex items-start justify-between gap-4 px-5.5 pt-5', props.class)">
        <slot />
    </div>
</template>
