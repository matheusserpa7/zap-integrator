<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Exceptions;

use Exception;

/**
 * Unused MessagingProvider paths until later milestones (sendText in M5, downloadMedia in M5).
 */
class ProviderOperationNotSupported extends Exception
{
    public function __construct(string $operation)
    {
        parent::__construct("Messaging provider operation [{$operation}] is not available in this milestone.");
    }
}
