<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Domain\Webhooks\Contracts\DnsResolver;

final class PhpDnsResolver implements DnsResolver
{
    /**
     * @return list<string>
     */
    public function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ipv4 = gethostbynamel($host) ?: [];
        $records = $ipv4;
        $aaaa = dns_get_record($host, DNS_AAAA) ?: [];

        foreach ($aaaa as $record) {
            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $records[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($records));
    }
}
