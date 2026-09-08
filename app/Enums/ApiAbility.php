<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiAbility: string
{
    case InstancesRead = 'instances:read';
    case InstancesWrite = 'instances:write';
    case MessagesSend = 'messages:send';
    case MessagesRead = 'messages:read';
    case MediaRead = 'media:read';
    case WebhooksRead = 'webhooks:read';
    case WebhooksWrite = 'webhooks:write';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $ability): string => $ability->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::InstancesRead => 'Ler instâncias',
            self::InstancesWrite => 'Gerenciar instâncias',
            self::MessagesSend => 'Enviar mensagens',
            self::MessagesRead => 'Ler mensagens',
            self::MediaRead => 'Ler mídia',
            self::WebhooksRead => 'Ler webhooks',
            self::WebhooksWrite => 'Gerenciar webhooks',
        };
    }
}
