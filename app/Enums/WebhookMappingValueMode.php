<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookMappingValueMode: string
{
    case Field = 'field';
    case Fixed = 'fixed';
    case Expression = 'expression';

    public function label(): string
    {
        return match ($this) {
            self::Field => 'Campo do evento',
            self::Fixed => 'Valor fixo',
            self::Expression => 'Expressão',
        };
    }
}
