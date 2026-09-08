<script setup lang="ts">
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    type DialogContentProps,
    useForwardProps,
} from 'reka-ui';
import { X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>();
const forwarded = useForwardProps(props);
</script>

<template>
    <DialogPortal>
        <DialogOverlay class="fixed inset-0 z-50 bg-dark/40 backdrop-blur-sm" />
        <DialogContent
            v-bind="forwarded"
            :class="
                cn(
                    'fixed top-1/2 left-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-lg border border-line bg-white p-6 shadow-zap',
                    props.class,
                )
            "
        >
            <slot />
            <DialogClose class="absolute top-4 right-4 rounded-lg p-1 text-muted hover:bg-green-50 hover:text-ink">
                <X class="size-4" />
                <span class="sr-only">Fechar</span>
            </DialogClose>
        </DialogContent>
    </DialogPortal>
</template>
