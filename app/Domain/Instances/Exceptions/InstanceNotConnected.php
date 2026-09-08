<?php

declare(strict_types=1);

namespace App\Domain\Instances\Exceptions;

use App\Support\ApiError;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InstanceNotConnected extends Exception
{
    public function __construct()
    {
        parent::__construct('Esta instância não está conectada.');
    }

    public function render(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->is('api/*')) {
            return ApiError::response(
                'instance_not_connected',
                'The instance is not connected.',
                422,
            );
        }

        return back()->with('error', $this->getMessage());
    }

    public function report(): false
    {
        return false;
    }
}
