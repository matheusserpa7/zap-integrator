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
    <Head title="Entrar" />
    <Card>
        <CardHeader>
            <div>
                <CardTitle>Entrar</CardTitle>
                <CardDescription>Acesse seu workspace ZAP com e-mail e senha.</CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <Form v-slot="{ errors, processing }" action="/login" method="post">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="email">E-mail</label>
                        <Input id="email" name="email" type="email" autocomplete="username" required />
                        <p v-if="errors.email" class="m-0 text-sm text-destructive">{{ errors.email }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-semibold" for="password">Senha</label>
                        <Input id="password" name="password" type="password" autocomplete="current-password" required />
                        <p v-if="errors.password" class="m-0 text-sm text-destructive">{{ errors.password }}</p>
                    </div>
                    <Button type="submit" :disabled="processing" class="mt-1 w-full">
                        {{ processing ? 'Entrando…' : 'Entrar' }}
                    </Button>
                </div>
            </Form>
            <p class="mt-5 mb-0 text-center text-sm text-muted">
                Não tem conta?
                <Link href="/register" class="font-semibold text-green-800 hover:underline">Criar conta</Link>
            </p>
        </CardContent>
    </Card>
</template>
