<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
</script>

<template>
    <Head title="Nova instância" />

    <section class="mb-6.5">
        <h1 class="m-0 text-[34px] leading-[1.08] tracking-tight">Nova instância</h1>
        <p class="mt-2 mb-0 text-[15px] text-muted">
            A conexão com o WhatsApp começa em seguida. Você verá o QR Code na próxima tela.
        </p>
    </section>

    <Card class="max-w-xl">
        <CardHeader>
            <div>
                <CardTitle>Identificação</CardTitle>
                <CardDescription>Escolha um nome interno. Ele não é enviado ao provedor de mensagens.</CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <Form v-slot="{ errors, processing }" action="/instances" method="post">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="name">Nome</label>
                        <Input
                            id="name"
                            name="name"
                            type="text"
                            maxlength="80"
                            required
                            placeholder="Atendimento principal"
                        />
                        <p v-if="errors.name" class="mt-0 mb-0 text-sm text-destructive">{{ errors.name }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Criando…' : 'Criar instância' }}
                        </Button>
                        <Link href="/instances" class="text-sm font-semibold text-muted no-underline hover:text-ink">
                            Cancelar
                        </Link>
                    </div>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
