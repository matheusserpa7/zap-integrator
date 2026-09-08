<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Data;

final readonly class ProviderMessage
{
    public function __construct(
        public string $providerMessageId,
        public string $status,
    ) {}
}
