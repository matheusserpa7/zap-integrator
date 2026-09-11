<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $workspace = $user instanceof User ? $user->currentWorkspace() : null;

        $reverbKey = config('broadcasting.connections.reverb.key');
        $reverbHost = config('broadcasting.connections.reverb.options.host');
        $reverbPort = config('broadcasting.connections.reverb.options.port');
        $reverbScheme = config('broadcasting.connections.reverb.options.scheme');

        return [
            ...parent::share($request),
            'workspace' => $workspace === null ? null : [
                'public_id' => $workspace->public_id,
                'name' => $workspace->name,
                'max_instances' => $workspace->maxInstancesLimit(),
            ],
            'reverb' => [
                'key' => is_string($reverbKey) && $reverbKey !== '' ? $reverbKey : null,
                'host' => is_string($reverbHost) && $reverbHost !== '' ? $reverbHost : 'localhost',
                'port' => is_numeric($reverbPort) ? (int) $reverbPort : 80,
                'scheme' => is_string($reverbScheme) && $reverbScheme !== '' ? $reverbScheme : 'http',
            ],
            'auth' => [
                'user' => $user instanceof User ? [
                    'public_id' => $user->public_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'initials' => $user->initials(),
                    'is_platform_admin' => $user->is_platform_admin,
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'plainTextToken' => $request->session()->get('plainTextToken'),
                'webhookSecret' => $request->session()->get('webhookSecret'),
            ],
        ];
    }
}
