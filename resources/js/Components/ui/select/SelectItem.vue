<script setup lang="ts">
import { SelectItem, SelectItemText, type SelectItemProps, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<SelectItemProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <SelectItem
        v-bind="forwarded"
        :class="
            cn(
                'relative flex w-full cursor-pointer items-center rounded-lg px-2.5 py-2 text-sm outline-none data-highlighted:bg-green-50 data-highlighted:text-green-800',
                props.class,
            )
        "
    >
        <SelectItemText>
            <slot />
        </SelectItemText>
    </SelectItem>
</template>
