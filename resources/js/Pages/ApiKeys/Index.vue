<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import type { SharedProps } from '@/Types';

type ApiKey = {
    public_id: string;
    name: string;
    token_prefix: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string | null;
};

type AbilityOption = {
    value: string;
    label: string;
};

const props = defineProps<{
    keys: ApiKey[];
    abilityOptions: AbilityOption[];
    canCreate: boolean;
}>();

const page = usePage<SharedProps>();
const createOpen = ref(false);
const plainTextOpen = ref(false);
const plainTextToken = ref('');
const copied = ref(false);

watch(
    () => page.props.flash.plainTextToken,
    (token) => {
        if (typeof token === 'string' && token !== '') {
            plainTextToken.value = token;
            copied.value = false;
            plainTextOpen.value = true;
        }
    },
    { immediate: true },
);

const abilityLabels = computed(() => {
    return Object.fromEntries(props.abilityOptions.map((option) => [option.value, option.label]));
});

function formatDate(value: string | null, empty = 'Nunca'): string {
    if (!value) {
        return empty;
    }

    return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value));
}

function maskedToken(prefix: string): string {
    return `${prefix}${'•'.repeat(16)}`;
}

async function copyPlainText(): Promise<void> {
    await globalThis.navigator.clipboard.writeText(plainTextToken.value);
    copied.value = true;
}

function revoke(publicId: string): void {
    if (!globalThis.confirm('Revogar esta chave? Aplicações que a usam deixarão de autenticar.')) {
        return;
    }

    router.delete(`/api-keys/${publicId}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="API Keys" />

    <section class="mb-6.5">
        <h1 class="m-0 text-[34px] leading-[1.08] tracking-tight">API Keys</h1>
        <p class="mt-2 mb-0 text-[15px] text-muted">
            Autentique integrações servidor a servidor com
            <span class="font-semibold text-ink">Authorization: Bearer</span>.
        </p>
    </section>

    <Card class="max-w-3xl">
        <CardHeader>
            <div>
                <CardTitle>Chave de API</CardTitle>
                <CardDescription>Use esta chave para autenticar suas requisições.</CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <div v-if="props.keys.length === 0" class="flex flex-col gap-3">
                <div
                    class="flex items-center justify-between gap-3 rounded-xl bg-[#0E201A] px-3.5 py-3.5 text-[#D8FCE4]"
                >
                    <span class="font-mono text-sm">zap_live_••••••••••••••••</span>
                    <Button size="sm" variant="outline" disabled>Copiar</Button>
                </div>
                <p class="mt-0 mb-0 text-[13px] text-muted">
                    Por segurança, a chave completa é exibida apenas uma vez ao ser criada.
                </p>
            </div>

            <ul v-else class="m-0 flex list-none flex-col gap-4 p-0">
                <li v-for="key in props.keys" :key="key.public_id" class="rounded-xl border border-line px-4 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <strong class="block text-sm tracking-tight">{{ key.name }}</strong>
                            <span class="mt-2 block font-mono text-sm text-[#0E201A]">{{
                                maskedToken(key.token_prefix)
                            }}</span>
                        </div>
                        <Button
                            v-if="props.canCreate"
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="revoke(key.public_id)"
                        >
                            Revogar
                        </Button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <Badge v-for="ability in key.abilities" :key="ability">
                            {{ abilityLabels[ability] ?? ability }}
                        </Badge>
                    </div>
                    <p class="mt-3 mb-0 text-[13px] text-muted">
                        Criada em {{ formatDate(key.created_at, '—') }} · Último uso
                        {{ formatDate(key.last_used_at) }}
                        <template v-if="key.expires_at"> · Expira em {{ formatDate(key.expires_at) }}</template>
                    </p>
                </li>
            </ul>

            <p v-if="props.keys.length > 0" class="mt-3 text-[13px] text-muted">
                Por segurança, a chave completa é exibida apenas uma vez ao ser criada.
            </p>
            <Button v-if="props.canCreate" variant="outline" class="mt-3.5" @click="createOpen = true">
                + Gerar nova chave
            </Button>
        </CardContent>
    </Card>

    <Dialog v-model:open="createOpen">
        <DialogContent>
            <DialogTitle>Gerar nova chave</DialogTitle>
            <p class="mt-1 mb-4 text-sm text-muted">
                Escolha um nome e as permissões desta chave. O valor secreto aparece só uma vez.
            </p>
            <Form
                v-slot="{ errors, processing }"
                action="/api-keys"
                method="post"
                reset-on-success
                @success="createOpen = false"
            >
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="name">Nome</label>
                        <Input id="name" name="name" maxlength="80" required placeholder="Integração de produção" />
                        <p v-if="errors.name" class="m-0 text-sm text-destructive">{{ errors.name }}</p>
                    </div>
                    <fieldset class="m-0 border-0 p-0">
                        <legend class="mb-2 text-sm font-semibold">Permissões</legend>
                        <div class="flex flex-col gap-2">
                            <label
                                v-for="option in props.abilityOptions"
                                :key="option.value"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="abilities[]"
                                    :value="option.value"
                                    class="size-4 rounded border-line accent-green-700"
                                />
                                <span>
                                    <span class="font-semibold">{{ option.label }}</span>
                                    <span class="text-muted"> · {{ option.value }}</span>
                                </span>
                            </label>
                        </div>
                        <p v-if="errors.abilities" class="mt-2 mb-0 text-sm text-destructive">{{ errors.abilities }}</p>
                    </fieldset>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="expires_at">Expiração (opcional)</label>
                        <Input id="expires_at" name="expires_at" type="datetime-local" />
                        <p v-if="errors.expires_at" class="m-0 text-sm text-destructive">{{ errors.expires_at }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Gerando…' : 'Gerar chave' }}
                        </Button>
                        <Button type="button" variant="outline" @click="createOpen = false">Cancelar</Button>
                    </div>
                </div>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="plainTextOpen">
        <DialogContent>
            <DialogTitle>Copie sua chave agora</DialogTitle>
            <p class="mt-1 mb-4 text-sm text-muted">
                Por segurança, a chave completa é exibida apenas uma vez ao ser criada.
            </p>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-[#0E201A] px-3.5 py-3.5 text-[#D8FCE4]">
                <span class="break-all font-mono text-sm" data-testid="plain-text-token">{{ plainTextToken }}</span>
                <Button size="sm" variant="outline" @click="copyPlainText">
                    {{ copied ? 'Copiado' : 'Copiar' }}
                </Button>
            </div>
            <Button class="mt-4" variant="outline" @click="plainTextOpen = false">Entendi</Button>
        </DialogContent>
    </Dialog>
</template>
