<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWorkspaceMembership
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        if ($user->hasWorkspace()) {
            return $next($request);
        }

        if ($user->is_platform_admin) {
            return redirect()->route('platform.allowlist.index');
        }

        abort(403);
    }
}
