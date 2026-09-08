<?php

declare(strict_types=1);

namespace App\Enums;

enum InstanceStatus: string
{
    case Creating = 'creating';
    case WaitingQr = 'waiting_qr';
    case Connecting = 'connecting';
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Error = 'error';
    case Deleting = 'deleting';

    public function isTerminalFailure(): bool
    {
        return $this === self::Error;
    }

    public function allowsQrRefresh(): bool
    {
        return in_array($this, [self::WaitingQr, self::Connecting, self::Disconnected, self::Error], true);
    }

    public function allowsDisconnect(): bool
    {
        return in_array($this, [self::WaitingQr, self::Connecting, self::Connected], true);
    }

    public function isBusy(): bool
    {
        return in_array($this, [self::Creating, self::Deleting], true);
    }
}
