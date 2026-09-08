<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Domain\Webhooks\Exceptions\WebhookHeaderNotAllowed;

final class ValidateWebhookHeaders
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN = [
        'host',
        'content-length',
        'transfer-encoding',
        'content-type',
        'user-agent',
    ];

    public function isAllowed(string $name): bool
    {
        $normalized = strtolower(trim($name));

        if ($normalized === '' || in_array($normalized, self::FORBIDDEN, true)) {
            return false;
        }

        if (str_starts_with($normalized, 'x-zap-')) {
            return false;
        }

        return $normalized === 'authorization'
            || $normalized === 'accept'
            || str_starts_with($normalized, 'x-');
    }

    /**
     * @param  array<int, array{name?: mixed, value_mode?: mixed, value?: mixed}>  $headers
     */
    public function __invoke(array $headers): void
    {
        foreach ($headers as $header) {
            $name = is_array($header) ? (string) ($header['name'] ?? '') : '';

            if (! $this->isAllowed($name)) {
                throw new WebhookHeaderNotAllowed("Header [{$name}] is not allowed.");
            }
        }
    }
}
