<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Data;

final readonly class ProviderMedia
{
    public function __construct(
        public string $contents,
        public string $mimeType,
        public ?string $filename = null,
    ) {}
}
