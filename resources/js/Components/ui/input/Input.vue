<script setup lang="ts">
import { cn } from '@/lib/utils';
import { type HTMLAttributes } from 'vue';
import { useVModel } from '@vueuse/core';

const props = defineProps<{
    defaultValue?: string | number;
    modelValue?: string | number;
    class?: HTMLAttributes['class'];
    type?: string;
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});
</script>

<template>
    <input
        v-model="modelValue"
        :type="type ?? 'text'"
        :class="
            cn(
                'flex h-11 w-full rounded-xl border border-line bg-[#FBFDFC] px-3.5 py-3 text-sm text-ink outline-none placeholder:text-muted focus-visible:border-green-500 focus-visible:shadow-[0_0_0_4px_rgba(37,211,102,.10)] disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
    />
</template>
