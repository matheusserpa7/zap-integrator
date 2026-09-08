<?php

declare(strict_types=1);

namespace App\Enums;

enum ProviderConnectionStatus: string
{
    case Connected = 'connected';
    case Connecting = 'connecting';
    case Disconnected = 'disconnected';
}
