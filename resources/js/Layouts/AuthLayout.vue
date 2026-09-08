<template>
    <div class="flex min-h-screen items-center justify-center px-6 py-12">
        <div class="w-full max-w-[440px]">
            <div class="mb-7 flex items-center justify-center gap-3">
                <div
                    class="grid size-[42px] place-items-center rounded-[14px] bg-linear-to-br from-green-700 to-green-500 text-lg font-black tracking-tight text-white shadow-[0_10px_22px_rgba(37,211,102,.24)]"
                    aria-hidden="true"
                >
                    Z
                </div>
                <div>
                    <strong class="block text-xl tracking-tight">ZAP</strong>
                    <span class="mt-px block text-xs text-muted">Evolution Connector</span>
                </div>
            </div>
            <slot />
        </div>
    </div>
</template>
