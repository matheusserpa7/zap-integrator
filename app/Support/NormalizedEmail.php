<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class NormalizedEmail
{
    public static function make(string $email): string
    {
        return Str::lower(trim($email));
    }
}
