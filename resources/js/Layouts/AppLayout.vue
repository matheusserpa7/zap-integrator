<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    HelpCircle,
    KeyRound,
    LayoutDashboard,
    LogOut,
    MessageSquare,
    Settings,
    Shield,
    Smartphone,
    Users,
    Webhook,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

type NavItem = {
    href: string;
    label: string;
    icon: typeof LayoutDashboard;
};

const workspaceNav: NavItem[] = [
    { href: '/', label: 'Dashboard', icon: LayoutDashboard },
    { href: '/inbox', label: 'Conversas', icon: MessageSquare },
    { href: '/instances', label: 'Instância', icon: Smartphone },
    { href: '/webhooks', label: 'Webhooks', icon: Webhook },
    { href: '/api-keys', label: 'API Keys', icon: KeyRound },
];

const accountNav: NavItem[] = [{ href: '/settings', label: 'Configurações', icon: Settings }];

const platformNav: NavItem[] = [
    { href: '/platform/allowlist', label: 'Lista de acesso', icon: Shield },
    { href: '/platform/users', label: 'Usuários', icon: Users },
];

const page = usePage();

function isActive(href: string): boolean {
    const current = page.url.split('?')[0] ?? '/';

    if (href === '/') {
        return current === '/';
    }

    return current === href || current.startsWith(`${href}/`);
}
</script>

<template>
    <div class="grid min-h-screen grid-cols-[260px_minmax(0,1fr)]">
        <aside
            class="sticky top-0 z-10 flex h-screen flex-col gap-7 border-r border-line bg-white/92 px-4.5 py-6 backdrop-blur-lg"
        >
            <div class="flex items-center gap-3 px-2 py-1">
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

            <nav v-if="page.props.workspace" class="flex flex-col gap-2" aria-label="Workspace">
                <div class="mx-2.5 mb-1 text-[11px] font-extrabold tracking-[0.12em] text-[#9AABA4] uppercase">
                    Workspace
                </div>
                <Link
                    v-for="item in workspaceNav"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold transition-colors',
                        isActive(item.href)
                            ? 'bg-green-100 text-green-900 shadow-[inset_0_0_0_1px_rgba(37,211,102,.12)]'
                            : 'text-[#53645D] hover:bg-green-50 hover:text-green-800',
                    ]"
                >
                    <component :is="item.icon" class="size-5 shrink-0" aria-hidden="true" />
                    {{ item.label }}
                </Link>
            </nav>

            <nav v-if="page.props.auth.user?.is_platform_admin" class="flex flex-col gap-2" aria-label="Plataforma">
                <div class="mx-2.5 mb-1 text-[11px] font-extrabold tracking-[0.12em] text-[#9AABA4] uppercase">
                    Plataforma
                </div>
                <Link
                    v-for="item in platformNav"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold transition-colors',
                        isActive(item.href)
                            ? 'bg-green-100 text-green-900 shadow-[inset_0_0_0_1px_rgba(37,211,102,.12)]'
                            : 'text-[#53645D] hover:bg-green-50 hover:text-green-800',
                    ]"
                >
                    <component :is="item.icon" class="size-5 shrink-0" aria-hidden="true" />
                    {{ item.label }}
                </Link>
            </nav>

            <nav class="flex flex-col gap-2" aria-label="Conta">
                <div class="mx-2.5 mb-1 text-[11px] font-extrabold tracking-[0.12em] text-[#9AABA4] uppercase">
                    Conta
                </div>
                <Link
                    v-for="item in accountNav"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold transition-colors',
                        isActive(item.href)
                            ? 'bg-green-100 text-green-900 shadow-[inset_0_0_0_1px_rgba(37,211,102,.12)]'
                            : 'text-[#53645D] hover:bg-green-50 hover:text-green-800',
                    ]"
                >
                    <component :is="item.icon" class="size-5 shrink-0" aria-hidden="true" />
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <div class="min-w-0">
            <header
                class="sticky top-0 z-20 flex items-center justify-between border-b border-line/90 bg-surface/86 px-[34px] py-[18px] backdrop-blur-xl"
            >
                <div>
                    <small class="mb-0.5 block text-xs text-muted">
                        {{ page.props.workspace ? 'Workspace' : 'Plataforma' }}
                    </small>
                    <strong class="text-base tracking-tight">
                        {{ page.props.workspace?.name ?? 'Administração' }}
                    </strong>
                </div>
                <div class="flex items-center gap-2.5">
                    <a
                        href="/docs/api"
                        class="grid size-10 place-items-center rounded-xl border border-line bg-white text-[#43564E]"
                        aria-label="Referência da API"
                    >
                        <HelpCircle class="size-4" />
                    </a>
                    <button
                        type="button"
                        class="grid size-10 place-items-center rounded-xl border border-line bg-white text-[#43564E]"
                        aria-label="Notificações"
                    >
                        <Bell class="size-4" />
                    </button>
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            class="grid size-10 place-items-center rounded-xl bg-linear-to-br from-green-700 to-green-500 text-sm font-extrabold text-white"
                            :aria-label="`Conta ${page.props.auth.user?.initials ?? ''}`"
                        >
                            {{ page.props.auth.user?.initials ?? '—' }}
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem>
                                <Link href="/settings" class="block w-full">Configurações</Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                                <Link href="/logout" method="post" as="button" class="flex w-full items-center gap-2">
                                    <LogOut class="size-4" aria-hidden="true" />
                                    Sair
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <div class="mx-auto max-w-[1440px] p-[34px]">
                <p
                    v-if="page.props.flash.success"
                    class="mb-5 rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800"
                    role="status"
                >
                    {{ page.props.flash.success }}
                </p>
                <p
                    v-if="page.props.flash.error"
                    class="mb-5 rounded-xl border border-destructive/20 bg-destructive/5 px-4 py-3 text-sm font-semibold text-destructive"
                    role="alert"
                >
                    {{ page.props.flash.error }}
                </p>
                <slot />
            </div>
        </div>
    </div>
</template>
