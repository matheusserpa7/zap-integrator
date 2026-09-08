<?php

declare(strict_types=1);

namespace App\Domain\Instances;

use App\Enums\InstanceStatus;
use App\Enums\ProviderConnectionStatus;

final class MapProviderConnectionState
{
    public function __invoke(InstanceStatus $current, ProviderConnectionStatus $provider): InstanceStatus
    {
        return match ($provider) {
            ProviderConnectionStatus::Connected => InstanceStatus::Connected,
            ProviderConnectionStatus::Connecting => InstanceStatus::Connecting,
            ProviderConnectionStatus::Disconnected => match ($current) {
                InstanceStatus::Connected, InstanceStatus::Disconnected => InstanceStatus::Disconnected,
                InstanceStatus::WaitingQr, InstanceStatus::Connecting => InstanceStatus::WaitingQr,
                default => $current,
            },
        };
    }
}
