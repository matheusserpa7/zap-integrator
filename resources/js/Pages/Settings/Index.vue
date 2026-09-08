<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
</script>

<template>
    <Head title="Configurações" />

    <Card class="max-w-xl">
        <CardHeader>
            <div>
                <CardTitle>Configurações</CardTitle>
                <CardDescription>
                    Conta autenticada por sessão. Recuperação de senha não está disponível.
                </CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <p class="m-0 text-sm text-muted">
                Se você esquecer a senha, peça para um administrador da plataforma excluir a conta. Com o e-mail na
                lista de acesso, será possível criar uma conta nova.
            </p>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="mt-5 inline-flex h-11 items-center justify-center rounded-[13px] border border-line bg-white px-4.5 text-sm font-extrabold text-ink hover:bg-green-50"
            >
                Sair
            </Link>
        </CardContent>
    </Card>
</template>
