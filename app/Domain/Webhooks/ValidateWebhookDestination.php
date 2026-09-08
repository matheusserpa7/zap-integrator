<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Domain\Webhooks\Contracts\DnsResolver;
use App\Domain\Webhooks\Exceptions\WebhookDestinationNotAllowed;
use Symfony\Component\HttpFoundation\IpUtils;

final class ValidateWebhookDestination
{
    /**
     * @var list<string>
     */
    private const BLOCKED_HOSTS = [
        'localhost',
        'localhost.localdomain',
        'metadata.google.internal',
        'metadata.goog',
        'metadata',
        'instance-data',
    ];

    /**
     * @var list<string>
     */
    private const ALWAYS_BLOCKED_RANGES = [
        '0.0.0.0/8',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '255.255.255.255/32',
        '100.64.0.0/10',
        '192.0.0.0/29',
        '192.0.2.0/24',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '::1/128',
        '::/128',
        'fe80::/10',
        'ff00::/8',
        'fc00::/7',
        '::ffff:127.0.0.0/104',
        '::ffff:169.254.0.0/112',
        '::ffff:10.0.0.0/104',
        '::ffff:172.16.0.0/108',
        '::ffff:192.168.0.0/112',
    ];

    /**
     * @var list<string>
     */
    private const PRIVATE_RANGES = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
    ];

    public function __construct(private DnsResolver $dns) {}

    /**
     * @return array{url: string, host: string, port: int, ip: string, scheme: string}
     */
    public function __invoke(string $url): array
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new WebhookDestinationNotAllowed('The webhook URL is invalid.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new WebhookDestinationNotAllowed('The webhook URL must not include credentials.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        $allowHttp = (bool) config('zap.webhooks.allow_http');

        if ($scheme === 'http' && ! $allowHttp) {
            throw new WebhookDestinationNotAllowed('Webhook URLs must use HTTPS.');
        }

        if (! in_array($scheme, ['https', 'http'], true)) {
            throw new WebhookDestinationNotAllowed('Webhook URLs must use HTTPS.');
        }

        $this->assertHostAllowed($host);

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        $resolved = $this->dns->resolve($host);

        if ($resolved === []) {
            throw new WebhookDestinationNotAllowed('The webhook host could not be resolved.');
        }

        foreach ($resolved as $ip) {
            $this->assertIpAllowed($ip);
        }

        return [
            'url' => $url,
            'host' => $host,
            'port' => $port,
            'ip' => $resolved[0],
            'scheme' => $scheme,
        ];
    }

    private function assertHostAllowed(string $host): void
    {
        if (str_ends_with($host, '.localhost') || in_array($host, self::BLOCKED_HOSTS, true)) {
            throw new WebhookDestinationNotAllowed('The webhook host is not allowed.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->assertIpAllowed($host);
        }
    }

    private function assertIpAllowed(string $ip): void
    {
        $ranges = self::ALWAYS_BLOCKED_RANGES;

        if (! (bool) config('zap.webhooks.allow_private_networks')) {
            $ranges = array_merge($ranges, self::PRIVATE_RANGES);
        }

        if (IpUtils::checkIp($ip, $ranges)) {
            throw new WebhookDestinationNotAllowed('The webhook destination resolves to a blocked address.');
        }
    }
}
