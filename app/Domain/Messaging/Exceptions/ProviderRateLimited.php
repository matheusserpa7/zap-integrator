<?php

declare(strict_types=1);

namespace App\Domain\Messaging\Exceptions;

use Exception;

class ProviderRateLimited extends Exception
{
    public function __construct()
    {
        parent::__construct('The messaging provider rate-limited the request.');
    }
}
