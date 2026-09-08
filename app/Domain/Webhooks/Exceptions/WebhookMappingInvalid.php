<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebhookMappingInvalid extends Exception
{
    public function __construct(string $message = 'The webhook mapping is invalid.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->is('api/*')) {
            return ApiError::response('webhook_mapping_invalid', $this->getMessage(), 422);
        }

        return back()->withErrors(['body_mapping' => $this->getMessage()]);
    }

    public function report(): false
    {
        return false;
    }
}
