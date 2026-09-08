<?php

declare(strict_types=1);

namespace App\Domain\ApiKeys\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdempotencyKeyRequired extends Exception
{
    public function __construct()
    {
        parent::__construct('The Idempotency-Key header is required.');
    }

    public function render(Request $request): JsonResponse
    {
        return ApiError::response('missing_idempotency_key', $this->getMessage(), 422);
    }

    public function report(): false
    {
        return false;
    }
}
