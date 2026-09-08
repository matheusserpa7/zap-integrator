<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\AuthenticatePublicApi;
use App\Http\Middleware\EnsureApiAbility;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureWorkspaceMembership;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use App\Support\RenderPublicApiException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/platform.php'));
            require base_path('routes/webhooks.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            AssignRequestId::class,
            AuthenticatePublicApi::class,
        ]);

        $middleware->alias([
            'platform' => EnsurePlatformAdmin::class,
            'active' => EnsureUserIsActive::class,
            'workspace' => EnsureWorkspaceMembership::class,
            'api.ability' => EnsureApiAbility::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'internal/webhooks/evolution/*',
        ]);

        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            return $user instanceof User ? $user->homePath() : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('internal/*')
                || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            return app(RenderPublicApiException::class)($e, $request);
        });
    })->create();
