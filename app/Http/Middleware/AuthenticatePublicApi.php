<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Workspace;
use App\Support\ApiError;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticatePublicApi
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! is_string($bearer) || $bearer === '') {
            return ApiError::response('unauthenticated', 'A Bearer token is required.', 401);
        }

        $token = PersonalAccessToken::findToken($bearer);

        if (! $token instanceof PersonalAccessToken || $this->isExpired($token)) {
            return ApiError::response('unauthenticated', 'The Bearer token is invalid.', 401);
        }

        $user = $token->tokenable;
        $workspace = $token->workspace;

        if (! $user instanceof User || $user->disabled_at !== null) {
            return ApiError::response('unauthenticated', 'The Bearer token is invalid.', 401);
        }

        if (! $workspace instanceof Workspace || ! $user->isMemberOf($workspace)) {
            return ApiError::response('unauthenticated', 'The Bearer token is invalid.', 401);
        }

        $user->withAccessToken($token);
        Auth::setUser($user);
        $request->setUserResolver(static fn (): User => $user);
        $request->attributes->set('workspace', $workspace);
        $request->attributes->set('api_token', $token);

        $token->forceFill(['last_used_at' => now()])->save();

        Context::add('workspace_id', $workspace->public_id);
        Context::add('user_id', $user->public_id);
        Context::add('api_token_id', $token->public_id);

        return $next($request);
    }

    private function isExpired(PersonalAccessToken $token): bool
    {
        return $token->expires_at !== null && $token->expires_at->isPast();
    }
}
