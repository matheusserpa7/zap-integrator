<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class PublicId
{
    public static function make(string $prefix): string
    {
        return $prefix.str_replace('-', '', Str::uuid7()->toString());
    }
}
