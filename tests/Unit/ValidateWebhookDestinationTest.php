<?php

use App\Domain\Webhooks\Contracts\DnsResolver;
use App\Domain\Webhooks\Exceptions\WebhookDestinationNotAllowed;
use App\Domain\Webhooks\ValidateWebhookDestination;

function destinationValidator(array $ips = ['93.184.216.34']): ValidateWebhookDestination
{
    $resolver = new class($ips) implements DnsResolver
    {
        /**
         * @param  list<string>  $ips
         */
        public function __construct(private array $ips) {}

        public function resolve(string $host): array
        {
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                return [$host];
            }

            return $this->ips;
        }
    };

    return new ValidateWebhookDestination($resolver);
}

it('rejects loopback addresses', function () {
    expect(fn () => destinationValidator()('http://127.0.0.1/hooks'))
        ->toThrow(WebhookDestinationNotAllowed::class);
});

it('rejects localhost hostnames', function () {
    expect(fn () => destinationValidator()('https://localhost/hooks'))
        ->toThrow(WebhookDestinationNotAllowed::class);
});

it('rejects cloud metadata addresses', function () {
    expect(fn () => destinationValidator()('https://169.254.169.254/latest/meta-data'))
        ->toThrow(WebhookDestinationNotAllowed::class);
});

it('rejects private rfc1918 addresses when private networks are disabled', function () {
    config(['zap.webhooks.allow_private_networks' => false]);

    expect(fn () => destinationValidator()('https://10.0.0.8/hooks'))
        ->toThrow(WebhookDestinationNotAllowed::class);
});

it('rejects a hostname that resolves to a loopback address', function () {
    expect(fn () => destinationValidator(['127.0.0.1'])('https://evil.example/hooks'))
        ->toThrow(WebhookDestinationNotAllowed::class);
});

it('accepts a public https host', function () {
    config(['zap.webhooks.allow_http' => false]);

    $resolved = destinationValidator()('https://example.com/webhooks/zap');

    expect($resolved['host'])->toBe('example.com')
        ->and($resolved['ip'])->toBe('93.184.216.34')
        ->and($resolved['port'])->toBe(443);
});

it('rejects http when http is not allowed', function () {
    config(['zap.webhooks.allow_http' => false]);

    expect(fn () => destinationValidator()('http://example.com/hooks'))
        ->toThrow(WebhookDestinationNotAllowed::class, 'Webhook URLs must use HTTPS.');
});
