<?php

declare(strict_types=1);

namespace App\Domain\ApiKeys\Data;

use App\Models\PersonalAccessToken;

final readonly class CreatedApiToken
{
    public function __construct(
        public PersonalAccessToken $token,
        public string $plainTextToken,
    ) {}
}
