<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookPayloadMode: string
{
    case Canonical = 'canonical';
    case Custom = 'custom';
}
