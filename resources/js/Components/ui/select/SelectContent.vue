<script setup lang="ts">
import { SelectContent, SelectPortal, SelectViewport, type SelectContentProps, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<SelectContentProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <SelectPortal>
        <SelectContent
            v-bind="forwarded"
            :class="
                cn(
                    'z-50 min-w-32 overflow-hidden rounded-xl border border-line bg-white p-1 shadow-zap-soft',
                    props.class,
                )
            "
        >
            <SelectViewport>
                <slot />
            </SelectViewport>
        </SelectContent>
    </SelectPortal>
</template>
