<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

final class RenderPublicApiException
{
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') || $e instanceof HttpResponseException) {
            return null;
        }

        if ($e instanceof MissingAbilityException) {
            return ApiError::response('missing_ability', 'This token does not have the required ability.', 403);
        }

        if ($e instanceof AuthenticationException) {
            return ApiError::response('unauthenticated', 'A Bearer token is required.', 401);
        }

        if ($e instanceof AuthorizationException) {
            return ApiError::response('forbidden', 'This action is unauthorized.', 403);
        }

        if ($e instanceof ValidationException) {
            $message = collect($e->errors())->flatten()->first();

            return ApiError::response(
                'validation_error',
                is_string($message) && $message !== '' ? $message : 'The given data was invalid.',
                422,
            );
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return ApiError::response('rate_limited', 'Too many requests.', 429, $e->getHeaders());
        }

        if ($e instanceof ModelNotFoundException) {
            return ApiError::response('not_found', 'The requested resource was not found.', 404);
        }

        if ($e instanceof HttpExceptionInterface) {
            return ApiError::response(
                $this->codeForStatus($e->getStatusCode()),
                $this->messageForStatus($e->getStatusCode(), $e->getMessage()),
                $e->getStatusCode(),
                $e->getHeaders(),
            );
        }

        if ($e instanceof SuspiciousOperationException) {
            return ApiError::response('not_found', 'The requested resource was not found.', 404);
        }

        return ApiError::response('internal_error', 'An unexpected error occurred.', 500);
    }

    private function codeForStatus(int $status): string
    {
        return match ($status) {
            401 => 'unauthenticated',
            403 => 'forbidden',
            404 => 'not_found',
            409 => 'conflict',
            413 => 'payload_too_large',
            422 => 'unprocessable_entity',
            429 => 'rate_limited',
            default => $status >= 500 ? 'internal_error' : 'http_error',
        };
    }

    private function messageForStatus(int $status, string $message): string
    {
        if ($status >= 500 || $message === '') {
            return match ($status) {
                401 => 'A Bearer token is required.',
                403 => 'This action is unauthorized.',
                404 => 'The requested resource was not found.',
                429 => 'Too many requests.',
                default => 'An unexpected error occurred.',
            };
        }

        return $message;
    }
}
