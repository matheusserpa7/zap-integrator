<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

final class CorrelationId
{
    public static function get(): string
    {
        $fromContext = Context::get('request_id');

        if (is_string($fromContext) && $fromContext !== '') {
            return $fromContext;
        }

        $existing = request()->headers->get('X-Request-Id');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $generated = 'req_'.str_replace('-', '', (string) Str::uuid());
        Context::add('request_id', $generated);

        return $generated;
    }
}
