<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Instances\Actions\CreateInstance;
use App\Domain\Instances\Actions\RequestInstanceQrRefresh;
use App\Http\Requests\Api\V1\StoreInstanceRequest;
use App\Http\Resources\Api\V1\InstanceResource;
use App\Models\Instance;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class InstanceController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $this->user($request);
        abort_unless($user->can('viewAny', Instance::class), 403);

        $workspace = $this->workspace($request);
        $instances = $workspace->instances()
            ->orderByDesc('id')
            ->get();

        return InstanceResource::collection($instances);
    }

    public function show(Request $request, Instance $instance): InstanceResource
    {
        $user = $this->user($request);
        abort_unless($user->can('view', $instance), 404);

        return InstanceResource::make($instance);
    }

    public function store(StoreInstanceRequest $request, CreateInstance $createInstance): JsonResponse
    {
        $instance = $createInstance(
            $this->workspace($request),
            $this->user($request),
            $request->string('name')->toString(),
        );

        return InstanceResource::make($instance)
            ->response()
            ->setStatusCode(201);
    }

    public function connection(Request $request, Instance $instance): JsonResponse
    {
        abort_unless($this->user($request)->can('view', $instance), 404);

        return response()->json([
            'data' => [
                'id' => $instance->public_id,
                'status' => $instance->status->value,
                'phone_number' => $instance->phone_number,
                'connected_at' => $instance->connected_at?->toIso8601String(),
                'last_seen_at' => $instance->last_seen_at?->toIso8601String(),
            ],
        ]);
    }

    public function refreshQr(Request $request, Instance $instance, RequestInstanceQrRefresh $refresh): JsonResponse
    {
        abort_unless($this->user($request)->can('refreshQr', $instance), 404);

        $refresh($instance);

        return response()->json([
            'data' => [
                'id' => $instance->public_id,
                'status' => $instance->status->value,
            ],
        ], 202);
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function workspace(Request $request): Workspace
    {
        $workspace = CurrentWorkspace::from($request);
        abort_unless($workspace instanceof Workspace, 401);

        return $workspace;
    }
}
