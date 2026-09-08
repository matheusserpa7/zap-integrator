<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

final class GenerateWebhookSecret
{
    public function __invoke(): string
    {
        return bin2hex(random_bytes(32));
    }
}
