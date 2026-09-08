<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Data;

final readonly class WebhookDeliveryAttempt
{
    public function __construct(
        public bool $successful,
        public ?int $httpStatus,
        public int $durationMs,
        public string $excerpt,
    ) {}
}
