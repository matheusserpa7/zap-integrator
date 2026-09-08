<script lang="ts">
import AuthLayout from '@/Layouts/AuthLayout.vue';

export default {
    layout: AuthLayout,
};
</script>

<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
</script>

<template>
    <Head title="Criar conta" />
    <Card>
        <CardHeader>
            <div>
                <CardTitle>Criar conta</CardTitle>
                <CardDescription>
                    Só é possível cadastrar e-mails autorizados pelo administrador da plataforma.
                </CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <Form v-slot="{ errors, processing }" action="/register" method="post">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="name">Nome</label>
                        <Input id="name" name="name" type="text" autocomplete="name" required />
                        <p v-if="errors.name" class="m-0 text-sm text-destructive">{{ errors.name }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="email">E-mail</label>
                        <Input id="email" name="email" type="email" autocomplete="username" required />
                        <p v-if="errors.email" class="m-0 text-sm text-destructive">{{ errors.email }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="password">Senha</label>
                        <Input id="password" name="password" type="password" autocomplete="new-password" required />
                        <p v-if="errors.password" class="m-0 text-sm text-destructive">{{ errors.password }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="password_confirmation">Confirmar senha</label>
                        <Input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                        />
                    </div>
                    <Button type="submit" :disabled="processing" class="mt-1 w-full">
                        {{ processing ? 'Criando conta…' : 'Criar conta' }}
                    </Button>
                </div>
            </Form>
            <p class="mt-5 mb-0 text-center text-sm text-muted">
                Já tem conta?
                <Link href="/login" class="font-semibold text-green-800 hover:underline">Entrar</Link>
            </p>
        </CardContent>
    </Card>
</template>
