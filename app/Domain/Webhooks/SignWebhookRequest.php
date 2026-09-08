<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

final class SignWebhookRequest
{
    public function timestamp(): int
    {
        return now()->getTimestamp();
    }

    public function signature(string $secret, int $timestamp, string $rawBody): string
    {
        return 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
    }

    public function verify(string $secret, int $timestamp, string $rawBody, string $header): bool
    {
        $expected = $this->signature($secret, $timestamp, $rawBody);

        return hash_equals($expected, $header);
    }
}
