<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Webhooks\Actions\CreateWebhookEndpoint;
use App\Domain\Webhooks\Actions\DeleteWebhookEndpoint;
use App\Domain\Webhooks\Actions\DuplicateWebhookEndpoint;
use App\Domain\Webhooks\Actions\RetryWebhookDelivery;
use App\Domain\Webhooks\Actions\TestWebhookEndpoint;
use App\Domain\Webhooks\Actions\UpdateWebhookEndpoint;
use App\Domain\Webhooks\GenerateWebhookSecret;
use App\Domain\Webhooks\NormalizeWebhookEndpointInput;
use App\Domain\Webhooks\PostSignedWebhook;
use App\Domain\Webhooks\ValidateWebhookDestination;
use App\Domain\Webhooks\ValidateWebhookHeaders;
use App\Domain\Webhooks\ValidateWebhookMapping;
use App\Domain\Webhooks\WebhookEventCatalog;
use App\Enums\ZapEventType;
use App\Http\Requests\StoreWebhookEndpointRequest;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WebhookBuilderController
{
    public function index(Request $request, WebhookEventCatalog $catalog): Response
    {
        $user = $this->user($request);
        abort_unless($user->can('viewAny', WebhookEndpoint::class), 403);

        $workspace = $this->workspace($user);
        $endpoints = $workspace->webhookEndpoints()
            ->orderByDesc('id')
            ->get()
            ->map(fn (WebhookEndpoint $endpoint): array => $endpoint->toInertia())
            ->all();

        return Inertia::render('Webhooks/Index', [
            'endpoints' => $endpoints,
            'canCreate' => $user->can('create', [WebhookEndpoint::class, $workspace]),
            'eventOptions' => $catalog->eventOptions(),
        ]);
    }

    public function create(Request $request, WebhookEventCatalog $catalog): Response
    {
        $user = $this->user($request);
        $workspace = $this->workspace($user);
        abort_unless($user->can('create', [WebhookEndpoint::class, $workspace]), 403);

        return $this->builder($catalog, $workspace, $user, null);
    }

    public function show(Request $request, WebhookEndpoint $endpoint, WebhookEventCatalog $catalog): Response
    {
        $user = $this->user($request);
        abort_unless($user->can('view', $endpoint), 404);

        return $this->builder($catalog, $this->workspace($user), $user, $endpoint);
    }

    public function store(StoreWebhookEndpointRequest $request, CreateWebhookEndpoint $create): RedirectResponse
    {
        $created = $create(
            $this->workspace($this->user($request)),
            $this->user($request),
            $request->endpointInput(),
        );

        return redirect()
            ->route('webhooks.show', $created->endpoint)
            ->with('success', 'Webhook salvo. Copie o segredo agora; ele não será exibido novamente.')
            ->with('webhookSecret', $created->plainTextSecret);
    }

    public function update(
        StoreWebhookEndpointRequest $request,
        WebhookEndpoint $endpoint,
        UpdateWebhookEndpoint $update,
    ): RedirectResponse {
        abort_unless($this->user($request)->can('update', $endpoint), 404);

        $updated = $update(
            $endpoint,
            $this->user($request),
            $request->endpointInput(),
            $request->boolean('rotate_secret'),
        );

        $redirect = redirect()
            ->route('webhooks.show', $updated)
            ->with('success', 'Webhook atualizado.');

        if ($request->boolean('rotate_secret')) {
            $redirect->with('webhookSecret', $updated->secret_encrypted)
                ->with('success', 'Segredo rotacionado. Copie agora; ele não será exibido novamente.');
        }

        return $redirect;
    }

    public function destroy(Request $request, WebhookEndpoint $endpoint, DeleteWebhookEndpoint $delete): RedirectResponse
    {
        abort_unless($this->user($request)->can('delete', $endpoint), 404);

        $delete($endpoint);

        return redirect()->route('webhooks.index')->with('success', 'Webhook excluído.');
    }

    public function duplicate(Request $request, WebhookEndpoint $endpoint, DuplicateWebhookEndpoint $duplicate): RedirectResponse
    {
        abort_unless($this->user($request)->can('duplicate', $endpoint), 404);

        $created = $duplicate($endpoint, $this->user($request));

        return redirect()
            ->route('webhooks.show', $created->endpoint)
            ->with('success', 'Webhook duplicado com um novo segredo. Copie agora.')
            ->with('webhookSecret', $created->plainTextSecret);
    }

    public function test(Request $request, WebhookEndpoint $endpoint, TestWebhookEndpoint $test): JsonResponse
    {
        abort_unless($this->user($request)->can('test', $endpoint), 404);

        $result = $test($endpoint);

        return response()->json([
            'ok' => $result->successful,
            'http_status' => $result->httpStatus,
            'duration_ms' => $result->durationMs,
            'excerpt' => $result->excerpt,
        ]);
    }

    public function testDraft(
        StoreWebhookEndpointRequest $request,
        NormalizeWebhookEndpointInput $normalize,
        ValidateWebhookDestination $validateDestination,
        ValidateWebhookMapping $validateMapping,
        ValidateWebhookHeaders $validateHeaders,
        GenerateWebhookSecret $generateSecret,
        PostSignedWebhook $post,
        WebhookEventCatalog $catalog,
    ): JsonResponse {
        $user = $this->user($request);
        $workspace = $this->workspace($user);
        abort_unless($user->can('create', [WebhookEndpoint::class, $workspace]), 403);

        $data = $normalize(
            $request->endpointInput(),
            $validateDestination,
            $validateMapping,
            $validateHeaders,
        );

        $endpoint = new WebhookEndpoint([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'description' => $data['description'],
            'event_type' => $data['event_type'],
            'payload_mode' => $data['payload_mode'],
            'body_mapping' => $data['body_mapping'],
            'headers_mapping_encrypted' => $data['headers'],
            'secret_encrypted' => $generateSecret(),
            'enabled' => true,
        ]);

        $fixture = $catalog->fixture($endpoint->event_type);
        $result = $post(
            $endpoint,
            $fixture,
            is_string($fixture['id'] ?? null) ? $fixture['id'] : 'evt_test',
            $endpoint->event_type,
            isTest: true,
        );

        return response()->json([
            'ok' => $result->successful,
            'http_status' => $result->httpStatus,
            'duration_ms' => $result->durationMs,
            'excerpt' => $result->excerpt,
        ]);
    }

    public function retry(Request $request, WebhookDelivery $delivery, RetryWebhookDelivery $retry): RedirectResponse
    {
        abort_unless($this->user($request)->can('retry', $delivery), 404);

        $retry($delivery);

        return back()->with('success', 'Nova tentativa de entrega enfileirada.');
    }

    private function builder(
        WebhookEventCatalog $catalog,
        Workspace $workspace,
        User $user,
        ?WebhookEndpoint $endpoint,
    ): Response {
        $deliveries = [];

        if ($endpoint instanceof WebhookEndpoint) {
            $deliveries = $endpoint->deliveries()
                ->with(['event', 'endpoint'])
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (WebhookDelivery $delivery): array => $delivery->toInertia())
                ->all();
        }

        $eventType = $endpoint instanceof WebhookEndpoint
            ? $endpoint->event_type
            : ZapEventType::MessageReceived;
        $fixtures = [];

        foreach (ZapEventType::customerFacing() as $type) {
            $fixtures[$type->value] = [
                'paths' => $catalog->paths($type),
                'aliases' => $catalog->aliases($type),
                'chips' => $catalog->chips($type),
                'fixture' => $catalog->fixture($type),
                'leaves' => $catalog->leafRows($catalog->fixture($type)),
            ];
        }

        return Inertia::render('Webhooks/Builder', [
            'endpoint' => $endpoint?->toInertia(),
            'deliveries' => $deliveries,
            'canEdit' => $endpoint instanceof WebhookEndpoint
                ? $user->can('update', $endpoint)
                : $user->can('create', [WebhookEndpoint::class, $workspace]),
            'eventOptions' => $catalog->eventOptions(),
            'catalog' => $fixtures,
            'defaultEventType' => $eventType->value,
        ]);
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
