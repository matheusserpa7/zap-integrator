<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageStatus: string
{
    case Accepted = 'accepted';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Received = 'received';
}
