<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WebhookHeaderNotAllowed extends Exception
{
    public function __construct(string $message = 'One of the extra headers is not allowed.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->is('api/*')) {
            return ApiError::response('webhook_header_not_allowed', $this->getMessage(), 422);
        }

        return back()->withErrors(['headers' => $this->getMessage()]);
    }

    public function report(): false
    {
        return false;
    }
}
