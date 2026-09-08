<?php

declare(strict_types=1);

namespace App\Support;

final class ConstantTime
{
    public static function equals(string $known, string $given): bool
    {
        return hash_equals(hash('sha256', $known), hash('sha256', $given));
    }
}
