<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebhookPayloadExpired extends Exception
{
    public function __construct()
    {
        parent::__construct('The webhook payload is no longer available.');
    }

    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->is('api/*')) {
            return ApiError::response('webhook_payload_expired', $this->getMessage(), 422);
        }

        return back()->with('error', 'O payload deste webhook já expirou e não pode ser reenviado.');
    }

    public function report(): false
    {
        return false;
    }
}
