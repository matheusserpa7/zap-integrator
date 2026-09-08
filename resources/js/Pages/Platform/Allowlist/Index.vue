<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';

defineProps<{
    emails: Array<{
        email: string;
        created_at: string | null;
    }>;
}>();

function removeEmail(email: string): void {
    router.delete(`/platform/allowlist/${encodeURIComponent(email)}`);
}
</script>

<template>
    <Head title="Lista de acesso" />

    <section class="mb-6.5">
        <h1 class="m-0 text-[34px] leading-[1.08] tracking-tight">Lista de acesso</h1>
        <p class="mt-2 mb-0 text-[15px] text-muted">Somente e-mails desta lista podem criar uma conta no ZAP.</p>
    </section>

    <Card class="mb-5">
        <CardHeader>
            <div>
                <CardTitle>Adicionar e-mail</CardTitle>
                <CardDescription>O endereço é normalizado em minúsculas antes de ser salvo.</CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <Form v-slot="{ errors, processing }" action="/platform/allowlist" method="post" reset-on-success>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1">
                        <label class="sr-only" for="email">E-mail</label>
                        <Input id="email" name="email" type="email" placeholder="pessoa@empresa.com" required />
                        <p v-if="errors.email" class="mt-1.5 mb-0 text-sm text-destructive">{{ errors.email }}</p>
                    </div>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Adicionando…' : 'Adicionar' }}
                    </Button>
                </div>
            </Form>
        </CardContent>
    </Card>

    <Card>
        <CardHeader>
            <div>
                <CardTitle>E-mails autorizados</CardTitle>
                <CardDescription v-if="emails.length === 0">Nenhum e-mail autorizado ainda.</CardDescription>
            </div>
        </CardHeader>
        <CardContent v-if="emails.length > 0" class="pt-0">
            <ul class="m-0 list-none divide-y divide-line p-0">
                <li v-for="entry in emails" :key="entry.email" class="flex items-center justify-between gap-4 py-3">
                    <span class="truncate text-sm font-semibold">{{ entry.email }}</span>
                    <Button type="button" variant="outline" size="sm" @click="removeEmail(entry.email)">
                        Remover
                    </Button>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
