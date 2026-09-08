<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApiAbility
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->currentAccessToken() === null || $user->tokenCant($ability)) {
            return ApiError::response('missing_ability', 'This token does not have the required ability.', 403);
        }

        return $next($request);
    }
}
