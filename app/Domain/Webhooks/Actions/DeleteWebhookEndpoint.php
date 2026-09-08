<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Models\WebhookEndpoint;

final class DeleteWebhookEndpoint
{
    public function __invoke(WebhookEndpoint $endpoint): void
    {
        $endpoint->delete();
    }
}
