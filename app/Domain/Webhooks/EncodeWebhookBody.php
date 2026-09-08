<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

final class EncodeWebhookBody
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(array $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
