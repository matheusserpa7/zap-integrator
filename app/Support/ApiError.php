<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiError
{
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function response(string $code, string $message, int $status, array $headers = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'request_id' => CorrelationId::get(),
            ],
        ], $status, $headers);
    }
}
