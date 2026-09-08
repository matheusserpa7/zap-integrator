<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[13px] text-sm font-extrabold transition-colors disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                default:
                    'bg-linear-to-br from-green-700 to-green-500 text-white shadow-[0_10px_24px_rgba(37,211,102,.22)] hover:opacity-95',
                outline: 'border border-green-700/20 bg-green-50 text-green-800 hover:bg-green-100',
                ghost: 'text-green-800 hover:bg-green-50',
                secondary: 'border border-line bg-white text-ink hover:bg-green-50',
                destructive: 'bg-destructive text-destructive-foreground hover:opacity-95',
            },
            size: {
                default: 'h-11 px-4.5 py-3',
                sm: 'h-9 rounded-xl px-3 text-xs',
                lg: 'h-12 px-5',
                icon: 'size-10 rounded-xl',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

type ButtonVariants = VariantProps<typeof buttonVariants>;

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariants['variant'];
        size?: ButtonVariants['size'];
        class?: HTMLAttributes['class'];
        type?: 'button' | 'submit' | 'reset';
    }>(),
    {
        type: 'button',
    },
);
</script>

<template>
    <button :type="props.type" :class="cn(buttonVariants({ variant, size }), props.class)">
        <slot />
    </button>
</template>
