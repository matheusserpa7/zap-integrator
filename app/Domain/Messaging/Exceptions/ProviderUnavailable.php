<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Exceptions;

use Exception;

class ProviderUnavailable extends Exception
{
    public function __construct()
    {
        parent::__construct('The messaging provider is unavailable.');
    }
}
