<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Platform\AssertAdminPasswordIsSafe;
use App\Domain\Webhooks\Contracts\DnsResolver;
use App\Domain\Webhooks\PhpDnsResolver;
use App\Integrations\Evolution\EvolutionMessagingProvider;
use App\Integrations\Fake\FakeMessagingProvider;
use App\Models\Conversation;
use App\Models\Instance;
use App\Models\MediaObject;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\Workspace;
use App\Support\ApiError;
use App\Support\CurrentWorkspace;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $driver = (string) config('zap.messaging.driver', 'evolution');
        $this->app->bind(
            MessagingProvider::class,
            $driver === 'fake' ? FakeMessagingProvider::class : EvolutionMessagingProvider::class,
        );
        $this->app->bind(DnsResolver::class, PhpDnsResolver::class);
    }

    public function boot(AssertAdminPasswordIsSafe $assertAdminPasswordIsSafe): void
    {
        $assertAdminPasswordIsSafe();

        Gate::define('viewApiDocs', function (?User $user = null): bool {
            if (app()->environment(['local', 'testing', 'e2e'])) {
                return true;
            }

            return $user instanceof User;
        });

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Route::bind('instance', function (string $value): Instance {
            $workspace = CurrentWorkspace::from(request());
            abort_unless($workspace instanceof Workspace, 404);

            return Instance::query()
                ->where('public_id', $value)
                ->where('workspace_id', $workspace->id)
                ->firstOrFail();
        });

        Route::bind('conversation', function (string $value): Conversation {
            $workspace = CurrentWorkspace::from(request());
            abort_unless($workspace instanceof Workspace, 404);

            return Conversation::query()
                ->where('public_id', $value)
                ->where('workspace_id', $workspace->id)
                ->firstOrFail();
        });

        Route::bind('media', function (string $value): MediaObject {
            $workspace = CurrentWorkspace::from(request());
            abort_unless($workspace instanceof Workspace, 404);

            return MediaObject::query()
                ->where('public_id', $value)
                ->where('workspace_id', $workspace->id)
                ->firstOrFail();
        });

        Route::bind('apiKey', function (string $value): PersonalAccessToken {
            $user = request()->user();
            abort_unless($user instanceof User, 404);

            $workspace = CurrentWorkspace::from(request());
            abort_unless($workspace instanceof Workspace, 404);

            return PersonalAccessToken::query()
                ->where('public_id', $value)
                ->where('workspace_id', $workspace->id)
                ->firstOrFail();
        });

        Route::bind('endpoint', function (string $value): WebhookEndpoint {
            $workspace = CurrentWorkspace::from(request());
            abort_unless($workspace instanceof Workspace, 404);

            return WebhookEndpoint::query()
                ->where('public_id', $value)
                ->where('workspace_id', $workspace->id)
                ->firstOrFail();
        });

        Route::bind('delivery', function (string $value): WebhookDelivery {
            $workspace = CurrentWorkspace::from(request());
            abort_unless($workspace instanceof Workspace, 404);

            return WebhookDelivery::query()
                ->where('public_id', $value)
                ->where('workspace_id', $workspace->id)
                ->firstOrFail();
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                mb_strtolower($request->string('email')->toString()).'|'.$request->ip()
            );
        });

        RateLimiter::for('evolution-webhooks', function (Request $request) {
            return Limit::perMinute(120)->by(
                (string) $request->route('instancePublicId').'|'.$request->ip()
            );
        });

        RateLimiter::for('public-api', function (Request $request) {
            $token = $request->attributes->get('api_token');
            $workspace = $request->attributes->get('workspace');

            $tokenKey = $token instanceof PersonalAccessToken
                ? 'token:'.$token->public_id
                : 'ip:'.$request->ip();
            $workspaceKey = $workspace instanceof Workspace
                ? 'workspace:'.$workspace->public_id
                : 'ip:'.$request->ip();

            $tokenLimit = max(1, (int) config('zap.api.rate_limit_per_token'));
            $workspaceLimit = max(1, (int) config('zap.api.rate_limit_per_workspace'));

            return [
                Limit::perMinute($tokenLimit)->by($tokenKey)->response(
                    fn (Request $request, array $headers) => ApiError::response(
                        'rate_limited',
                        'Too many requests.',
                        429,
                        $headers,
                    ),
                ),
                Limit::perMinute($workspaceLimit)->by($workspaceKey)->response(
                    fn (Request $request, array $headers) => ApiError::response(
                        'rate_limited',
                        'Too many requests.',
                        429,
                        $headers,
                    ),
                ),
            ];
        });
    }
}
