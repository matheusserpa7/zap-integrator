<?php

declare(strict_types=1);

namespace App\Domain\ApiKeys\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IdempotencyKeyConflict extends Exception
{
    public function __construct()
    {
        parent::__construct('The Idempotency-Key was reused with a different request body.');
    }

    public function render(Request $request): JsonResponse
    {
        return ApiError::response('idempotency_conflict', $this->getMessage(), 409);
    }

    public function report(): false
    {
        return false;
    }
}
