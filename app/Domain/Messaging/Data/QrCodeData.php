<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Data;

use DateTimeImmutable;

final readonly class QrCodeData
{
    public function __construct(
        public string $image,
        public DateTimeImmutable $expiresAt,
    ) {}
}
