<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Data;

final readonly class DownloadMediaData
{
    public function __construct(
        public string $instanceName,
        public string $providerMessageId,
    ) {}
}
