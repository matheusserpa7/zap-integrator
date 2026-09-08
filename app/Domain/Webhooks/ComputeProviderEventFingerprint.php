<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Domain\Webhooks\Data\NormalizedProviderEvent;

final class ComputeProviderEventFingerprint
{
    public function __invoke(int $instanceId, NormalizedProviderEvent $event): string
    {
        if (is_string($event->providerEventId) && $event->providerEventId !== '') {
            return hash('sha256', $instanceId.'|'.$event->providerEventType.'|'.$event->providerEventId);
        }

        return hash('sha256', $instanceId.'|'.$event->providerEventType.'|'.$event->canonicalPayload);
    }
}
