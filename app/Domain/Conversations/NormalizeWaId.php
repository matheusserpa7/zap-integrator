<?php

declare(strict_types=1);

namespace App\Domain\Conversations;

final class NormalizeWaId
{
    public function __invoke(string $raw): string
    {
        $jid = explode('@', $raw, 2)[0];
        $digits = preg_replace('/\D+/', '', $jid) ?? '';

        return $digits;
    }

    public function isGroup(string $raw): bool
    {
        $lower = strtolower($raw);

        return str_contains($lower, '@g.us')
            || str_contains($lower, '@broadcast')
            || str_ends_with($lower, '@newsletter');
    }
}
