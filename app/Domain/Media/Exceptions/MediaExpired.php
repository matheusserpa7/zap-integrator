<?php

declare(strict_types=1);

namespace App\Domain\Media\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaExpired extends Exception
{
    public function __construct()
    {
        parent::__construct('The media object has expired.');
    }

    public function render(Request $request): JsonResponse
    {
        return ApiError::response('media_expired', $this->getMessage(), 410);
    }

    public function report(): false
    {
        return false;
    }
}
