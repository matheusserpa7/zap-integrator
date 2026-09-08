<?php

declare(strict_types=1);

namespace App\Enums;

enum ZapEventType: string
{
    case InstanceQrUpdated = 'instance.qr.updated';
    case InstanceConnectionUpdated = 'instance.connection.updated';
    case InstanceConnected = 'instance.connected';
    case InstanceDisconnected = 'instance.disconnected';
    case MessageReceived = 'message.received';
    case MessageUpdated = 'message.updated';
    case MessageSent = 'message.sent';
    case MessageFailed = 'message.failed';
    case Ignored = 'provider.ignored';

    public function queue(): string
    {
        return match ($this) {
            self::InstanceQrUpdated, self::InstanceConnectionUpdated => 'critical',
            default => 'provider',
        };
    }

    public function isCustomerFacing(): bool
    {
        return match ($this) {
            self::Ignored, self::InstanceConnectionUpdated => false,
            default => true,
        };
    }

    /**
     * @return list<self>
     */
    public static function customerFacing(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type): bool => $type->isCustomerFacing(),
        ));
    }
}
