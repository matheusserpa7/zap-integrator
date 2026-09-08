<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Data;

use App\Models\WebhookEndpoint;

final readonly class CreatedWebhookEndpoint
{
    public function __construct(
        public WebhookEndpoint $endpoint,
        public string $plainTextSecret,
    ) {}
}
