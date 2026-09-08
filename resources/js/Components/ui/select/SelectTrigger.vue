<script setup lang="ts">
import { SelectTrigger, type SelectTriggerProps, useForwardProps } from 'reka-ui';
import { ChevronDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<SelectTriggerProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <SelectTrigger
        v-bind="forwarded"
        :class="
            cn(
                'flex h-11 w-full items-center justify-between rounded-xl border border-line bg-[#FBFDFC] px-3.5 text-sm text-ink outline-none focus-visible:border-green-500 disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    >
        <slot />
        <ChevronDown class="size-4 opacity-60" />
    </SelectTrigger>
</template>
