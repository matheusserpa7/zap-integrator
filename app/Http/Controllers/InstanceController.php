<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Instances\Actions\CreateInstance;
use App\Domain\Instances\Actions\RequestInstanceDeletion;
use App\Domain\Instances\Actions\RequestInstanceDisconnect;
use App\Domain\Instances\Actions\RequestInstanceQrRefresh;
use App\Enums\InstanceStatus;
use App\Http\Requests\StoreInstanceRequest;
use App\Jobs\SyncInstanceConnection;
use App\Models\Instance;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InstanceController
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        abort_unless($user->can('viewAny', Instance::class), 403);

        $workspace = $this->workspace($user);
        $instances = $workspace->instances()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Instance $instance): array => $instance->toInertia())
            ->all();

        return Inertia::render('Instances/Index', [
            'instances' => $instances,
            'canCreate' => $user->can('create', [Instance::class, $workspace])
                && $workspace->instances()->count() < $workspace->maxInstancesLimit(),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $user = $this->user($request);
        $workspace = $this->workspace($user);
        abort_unless($user->can('create', [Instance::class, $workspace]), 403);

        if ($workspace->instances()->count() >= $workspace->maxInstancesLimit()) {
            return redirect()
                ->route('instances.index')
                ->with('error', 'Este workspace já atingiu o limite de instâncias.');
        }

        return Inertia::render('Instances/Create');
    }

    public function store(StoreInstanceRequest $request, CreateInstance $createInstance): RedirectResponse
    {
        $user = $this->user($request);
        $workspace = $this->workspace($user);

        $instance = $createInstance(
            $workspace,
            $user,
            $request->string('name')->toString(),
        );

        return redirect()
            ->route('instances.show', $instance)
            ->with('success', 'Instância criada. O QR Code aparece em instantes.');
    }

    public function show(Request $request, Instance $instance): Response
    {
        $user = $this->user($request);
        abort_unless($user->can('view', $instance), 404);

        $instance->loadMissing('workspace');

        if (in_array($instance->status, [InstanceStatus::WaitingQr, InstanceStatus::Connecting], true)
            && ($instance->last_seen_at === null || $instance->last_seen_at->lt(now()->subSeconds((int) config('zap.instances.connection_sync_interval_seconds', 15))))) {
            SyncInstanceConnection::dispatch($instance->id);
        }

        return Inertia::render('Instances/Show', [
            'instance' => $instance->toInertia(includeQr: true),
            'canRefreshQr' => $user->can('refreshQr', $instance) && $instance->status->allowsQrRefresh(),
            'canDisconnect' => $user->can('disconnect', $instance) && $instance->status->allowsDisconnect(),
            'canDelete' => $user->can('delete', $instance) && $instance->status !== InstanceStatus::Deleting,
        ]);
    }

    public function refreshQr(Request $request, Instance $instance, RequestInstanceQrRefresh $refresh): RedirectResponse
    {
        $user = $this->user($request);
        abort_unless($user->can('refreshQr', $instance), 404);

        $refresh($instance);

        return back()->with('success', 'Atualizando o QR Code…');
    }

    public function disconnect(Request $request, Instance $instance, RequestInstanceDisconnect $disconnect): RedirectResponse
    {
        $user = $this->user($request);
        abort_unless($user->can('disconnect', $instance), 404);

        $disconnect($instance, $user);

        return back()->with('success', 'Desconectando a instância…');
    }

    public function destroy(Request $request, Instance $instance, RequestInstanceDeletion $delete): RedirectResponse
    {
        $user = $this->user($request);
        abort_unless($user->can('delete', $instance), 404);

        $delete($instance, $user);

        return redirect()
            ->route('instances.index')
            ->with('success', 'A instância está sendo excluída.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function workspace(User $user): Workspace
    {
        $workspace = $user->currentWorkspace();
        abort_unless($workspace instanceof Workspace, 403);

        return $workspace;
    }
}
