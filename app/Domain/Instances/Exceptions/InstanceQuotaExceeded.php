<?php

declare(strict_types=1);

namespace App\Domain\Instances\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstanceQuotaExceeded extends Exception
{
    public function __construct()
    {
        parent::__construct('Este workspace já atingiu o limite de instâncias.');
    }

    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->is('api/*')) {
            return ApiError::response(
                'instance_quota_exceeded',
                'This workspace has reached its instance limit.',
                422,
            );
        }

        return back()->withErrors(['name' => $this->getMessage()]);
    }

    public function report(): false
    {
        return false;
    }
}
