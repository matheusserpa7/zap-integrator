<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookDeliveryStatus: string
{
    case Pending = 'pending';
    case Retrying = 'retrying';
    case Delivered = 'delivered';
    case DeadLetter = 'dead_letter';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Retrying => 'Tentando novamente',
            self::Delivered => 'Entregue',
            self::DeadLetter => 'Falha permanente',
        };
    }
}
