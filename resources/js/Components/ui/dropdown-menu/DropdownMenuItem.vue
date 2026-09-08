<script setup lang="ts">
import { DropdownMenuItem, type DropdownMenuItemProps, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<DropdownMenuItemProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <DropdownMenuItem
        v-bind="forwarded"
        :class="
            cn(
                'flex cursor-pointer items-center rounded-lg px-2.5 py-2 text-sm text-ink outline-none data-highlighted:bg-green-50 data-highlighted:text-green-800',
                props.class,
            )
        "
    >
        <slot />
    </DropdownMenuItem>
</template>
