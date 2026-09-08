<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Webhooks\Actions\CreateWebhookEndpoint;
use App\Domain\Webhooks\Actions\DeleteWebhookEndpoint;
use App\Domain\Webhooks\Actions\DuplicateWebhookEndpoint;
use App\Domain\Webhooks\Actions\RetryWebhookDelivery;
use App\Domain\Webhooks\Actions\TestWebhookEndpoint;
use App\Domain\Webhooks\Actions\UpdateWebhookEndpoint;
use App\Http\Requests\Api\V1\StoreWebhookEndpointRequest;
use App\Http\Resources\Api\V1\WebhookDeliveryResource;
use App\Http\Resources\Api\V1\WebhookEndpointResource;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class WebhookEndpointController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($this->user($request)->can('viewAny', WebhookEndpoint::class), 403);

        $endpoints = $this->workspace($request)
            ->webhookEndpoints()
            ->orderByDesc('id')
            ->paginate(20);

        return WebhookEndpointResource::collection($endpoints);
    }

    public function store(StoreWebhookEndpointRequest $request, CreateWebhookEndpoint $create): JsonResponse
    {
        $created = $create(
            $this->workspace($request),
            $this->user($request),
            $request->endpointInput(),
        );

        $resource = WebhookEndpointResource::make($created->endpoint);
        $resource->plainTextSecret = $created->plainTextSecret;

        return $resource->response()->setStatusCode(201);
    }

    public function update(
        StoreWebhookEndpointRequest $request,
        WebhookEndpoint $endpoint,
        UpdateWebhookEndpoint $update,
    ): WebhookEndpointResource {
        abort_unless($this->user($request)->can('update', $endpoint), 404);

        $updated = $update(
            $endpoint,
            $this->user($request),
            $request->endpointInput(),
            $request->boolean('rotate_secret'),
        );

        $resource = WebhookEndpointResource::make($updated);

        if ($request->boolean('rotate_secret')) {
            $resource->plainTextSecret = $updated->secret_encrypted;
        }

        return $resource;
    }

    public function destroy(Request $request, WebhookEndpoint $endpoint, DeleteWebhookEndpoint $delete): Response
    {
        abort_unless($this->user($request)->can('delete', $endpoint), 404);

        $delete($endpoint);

        return response()->noContent();
    }

    public function test(Request $request, WebhookEndpoint $endpoint, TestWebhookEndpoint $test): JsonResponse
    {
        abort_unless($this->user($request)->can('test', $endpoint), 404);

        $result = $test($endpoint);

        return response()->json([
            'data' => [
                'ok' => $result->successful,
                'http_status' => $result->httpStatus,
                'duration_ms' => $result->durationMs,
                'excerpt' => $result->excerpt,
            ],
        ]);
    }

    public function duplicate(Request $request, WebhookEndpoint $endpoint, DuplicateWebhookEndpoint $duplicate): JsonResponse
    {
        abort_unless($this->user($request)->can('duplicate', $endpoint), 404);

        $created = $duplicate($endpoint, $this->user($request));
        $resource = WebhookEndpointResource::make($created->endpoint);
        $resource->plainTextSecret = $created->plainTextSecret;

        return $resource->response()->setStatusCode(201);
    }

    public function deliveries(Request $request): AnonymousResourceCollection
    {
        abort_unless($this->user($request)->can('viewAny', WebhookDelivery::class), 403);

        $deliveries = $this->workspace($request)
            ->webhookDeliveries()
            ->with(['event', 'endpoint'])
            ->orderByDesc('id')
            ->paginate(20);

        return WebhookDeliveryResource::collection($deliveries);
    }

    public function retry(Request $request, WebhookDelivery $delivery, RetryWebhookDelivery $retry): WebhookDeliveryResource
    {
        abort_unless($this->user($request)->can('retry', $delivery), 404);

        return WebhookDeliveryResource::make($retry($delivery)->load(['event', 'endpoint']));
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
