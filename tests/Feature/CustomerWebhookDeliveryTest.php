<?php

use App\Domain\Webhooks\Actions\FanOutWebhookEvent;
use App\Domain\Webhooks\PostSignedWebhook;
use App\Domain\Webhooks\SignWebhookRequest;
use App\Enums\ApiAbility;
use App\Enums\WebhookDeliveryStatus;
use App\Enums\ZapEventType;
use App\Jobs\DeliverCustomerWebhook;
use App\Models\Instance;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('signs the canonical body with hmac sha256', function () {
    fakePublicWebhookDns();
    Http::preventStrayRequests();
    Http::fake(['https://example.com/webhooks/zap' => Http::response('ok', 200)]);

    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->connected()->create();
    $endpoint = WebhookEndpoint::factory()->for($workspace)->create();
    $event = WebhookEvent::factory()->for($workspace)->for($instance)->create([
        'type' => ZapEventType::MessageReceived,
    ]);

    app(FanOutWebhookEvent::class)($event);

    $delivery = WebhookDelivery::query()->first();

    expect($delivery?->status)->toBe(WebhookDeliveryStatus::Delivered)
        ->and($delivery?->http_status)->toBe(200);

    Http::assertSent(function (Request $request) use ($endpoint, $event): bool {
        $timestamp = (int) $request->header('X-ZAP-Timestamp')[0];
        $signature = (string) $request->header('X-ZAP-Signature')[0];
        $signer = new SignWebhookRequest;

        return $request->url() === 'https://example.com/webhooks/zap'
            && $request->hasHeader('User-Agent', 'ZAP-Webhooks/1.0')
            && $request->hasHeader('X-ZAP-Event-Id', $event->public_id)
            && ! $request->hasHeader('X-ZAP-Test')
            && $signer->verify($endpoint->secret_encrypted, $timestamp, $request->body(), $signature)
            && str_contains($request->body(), '"type":"message.received"');
    });
});

it('signs the mapped custom body rather than the canonical envelope', function () {
    fakePublicWebhookDns();
    Http::preventStrayRequests();
    Http::fake(['https://example.com/webhooks/zap' => Http::response('ok', 200)]);

    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->connected()->create();
    $endpoint = WebhookEndpoint::factory()->for($workspace)->custom()->create();
    $event = WebhookEvent::factory()->for($workspace)->for($instance)->create();

    app(FanOutWebhookEvent::class)($event);

    Http::assertSent(function (Request $request) use ($endpoint): bool {
        $timestamp = (int) $request->header('X-ZAP-Timestamp')[0];
        $signature = (string) $request->header('X-ZAP-Signature')[0];
        $signer = new SignWebhookRequest;
        $body = $request->body();

        return $signer->verify($endpoint->secret_encrypted, $timestamp, $body, $signature)
            && str_contains($body, '"source":"zap"')
            && ! str_contains($body, '"created_at"');
    });
});

it('does not create a second delivery when the same event is fanned out twice', function () {
    Queue::fake();

    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->connected()->create();
    WebhookEndpoint::factory()->for($workspace)->create();
    $event = WebhookEvent::factory()->for($workspace)->for($instance)->create();

    $fanOut = app(FanOutWebhookEvent::class);
    $fanOut($event);
    $fanOut($event);

    expect(WebhookDelivery::query()->count())->toBe(1);
    Queue::assertPushed(DeliverCustomerWebhook::class, 1);
});

it('retries a failed delivery and marks it dead letter after the last attempt', function () {
    Queue::fake();
    fakePublicWebhookDns();
    Http::preventStrayRequests();
    Http::fake(['https://example.com/webhooks/zap' => Http::response('nope', 500)]);

    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->connected()->create();
    $endpoint = WebhookEndpoint::factory()->for($workspace)->create();
    $event = WebhookEvent::factory()->for($workspace)->for($instance)->create();

    app(FanOutWebhookEvent::class)($event);

    $delivery = WebhookDelivery::query()->first();
    $post = app(PostSignedWebhook::class);

    for ($attempt = 1; $attempt <= 7; $attempt++) {
        (new DeliverCustomerWebhook($delivery->id, $attempt))->handle($post);
        $delivery->refresh();
    }

    expect($delivery->status)->toBe(WebhookDeliveryStatus::DeadLetter)
        ->and($delivery->attempt)->toBe(7)
        ->and($endpoint->fresh()?->failure_count)->toBe(7);
});

it('rejects private destinations for live delivery', function () {
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->connected()->create();
    $endpoint = WebhookEndpoint::factory()->for($workspace)->create([
        'url' => 'http://127.0.0.1/hooks',
    ]);
    $event = WebhookEvent::factory()->for($workspace)->for($instance)->create();

    app(FanOutWebhookEvent::class)($event);

    $delivery = WebhookDelivery::query()->first();

    expect($delivery?->status)->toBe(WebhookDeliveryStatus::DeadLetter)
        ->and($delivery?->response_excerpt)->toBe('destination_not_allowed');

    Http::assertNothingSent();
});

it('does not queue retries for the test button', function () {
    Queue::fake();
    fakePublicWebhookDns();
    Http::preventStrayRequests();
    Http::fake(['https://example.com/webhooks/zap' => Http::response('ok', 200)]);

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value]);
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-endpoints.test', $endpoint))
        ->assertOk()
        ->assertJsonPath('data.ok', true);

    Queue::assertNothingPushed();
    expect(WebhookDelivery::query()->count())->toBe(0);
});
