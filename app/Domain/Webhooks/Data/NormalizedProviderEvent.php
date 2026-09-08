<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Data;

use App\Enums\ZapEventType;

final readonly class NormalizedProviderEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $providerEventType,
        public ZapEventType $eventType,
        public ?string $providerEventId,
        public array $payload,
        public string $canonicalPayload,
    ) {}
}
