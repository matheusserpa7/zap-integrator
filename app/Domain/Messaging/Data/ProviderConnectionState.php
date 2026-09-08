<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Data;

use App\Enums\ProviderConnectionStatus;

final readonly class ProviderConnectionState
{
    public function __construct(
        public ProviderConnectionStatus $status,
        public ?string $phoneNumber = null,
    ) {}
}
