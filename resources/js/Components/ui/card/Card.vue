<script setup lang="ts">
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'overflow-hidden rounded-lg border border-line bg-white shadow-[0_8px_30px_rgba(13,37,29,.035)]',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
