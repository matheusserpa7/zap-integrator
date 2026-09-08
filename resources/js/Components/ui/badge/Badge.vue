<script setup lang="ts">
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        variant?: 'default' | 'success';
    }>(),
    {
        variant: 'default',
    },
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-black',
                variant === 'success' && 'bg-green-100 text-green-800',
                variant === 'default' && 'bg-green-50 text-green-800',
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>
