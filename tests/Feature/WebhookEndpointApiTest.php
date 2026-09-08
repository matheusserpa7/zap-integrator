<?php

use App\Enums\ApiAbility;
use App\Enums\AuditAction;
use App\Enums\WebhookPayloadMode;
use App\Enums\ZapEventType;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;

it('returns 401 when no bearer token is provided', function () {
    $this->getJson(route('api.v1.webhook-endpoints.index'))
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('returns 403 when the token is missing webhooks:read', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.webhook-endpoints.index'))
        ->assertForbidden()
        ->assertJsonPath('error.code', 'missing_ability');
});

it('creates a canonical endpoint, returns the secret once, and records an audit event', function () {
    fakePublicWebhookDns();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value, ApiAbility::WebhooksRead->value]);

    $response = $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-endpoints.store'), [
            'name' => 'CRM',
            'url' => 'https://example.com/webhooks/zap',
            'event_type' => ZapEventType::MessageReceived->value,
            'payload_mode' => WebhookPayloadMode::Canonical->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'CRM')
        ->assertJsonPath('data.payload_mode', 'canonical')
        ->assertJsonMissingPath('data.secret_encrypted');

    $secret = $response->json('data.secret');
    $endpoint = WebhookEndpoint::query()->first();

    expect($endpoint)->not->toBeNull()
        ->and($secret)->toBeString()
        ->and(strlen((string) $secret))->toBeGreaterThanOrEqual(64)
        ->and($endpoint?->secret_encrypted)->toBe($secret);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.webhook-endpoints.index'))
        ->assertOk()
        ->assertJsonPath('data.0.secret', null);

    $audit = AuditLog::query()->where('action', AuditAction::WebhookEndpointCreated)->first();

    expect($audit)->not->toBeNull()
        ->and(json_encode($audit?->metadata))->not->toContain((string) $secret);
});

it('saves a custom map without requiring a successful test', function () {
    fakePublicWebhookDns();
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-endpoints.store'), [
            'name' => 'CRM custom',
            'url' => 'https://example.com/webhooks/zap',
            'event_type' => ZapEventType::MessageReceived->value,
            'payload_mode' => WebhookPayloadMode::Custom->value,
            'body_mapping' => [
                ['path' => 'customer.phone', 'value_mode' => 'field', 'value' => 'data.from'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.payload_mode', 'custom');

    expect(WebhookEndpoint::query()->count())->toBe(1);
    Http::assertNothingSent();
});

it('returns 422 when a mapping path is not allowlisted', function () {
    fakePublicWebhookDns();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-endpoints.store'), [
            'name' => 'CRM',
            'url' => 'https://example.com/webhooks/zap',
            'event_type' => ZapEventType::MessageReceived->value,
            'payload_mode' => WebhookPayloadMode::Custom->value,
            'body_mapping' => [
                ['path' => 'oops', 'value_mode' => 'field', 'value' => 'process.env'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'webhook_mapping_invalid');

    expect(WebhookEndpoint::query()->count())->toBe(0);
});

it('returns 422 when a forbidden extra header is submitted', function () {
    fakePublicWebhookDns();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-endpoints.store'), [
            'name' => 'CRM',
            'url' => 'https://example.com/webhooks/zap',
            'event_type' => ZapEventType::MessageReceived->value,
            'payload_mode' => WebhookPayloadMode::Canonical->value,
            'headers' => [
                ['name' => 'X-ZAP-Signature', 'value_mode' => 'fixed', 'value' => 'nope'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'webhook_header_not_allowed');
});

it('returns 422 when the destination is a private ip', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-endpoints.store'), [
            'name' => 'SSRFy',
            'url' => 'http://127.0.0.1/hooks',
            'event_type' => ZapEventType::MessageReceived->value,
            'payload_mode' => WebhookPayloadMode::Canonical->value,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'webhook_destination_not_allowed');
});

it('returns 404 for another workspace endpoint', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();
    $created = createWorkspaceApiToken($stranger, [ApiAbility::WebhooksRead->value, ApiAbility::WebhooksWrite->value]);

    $this->withToken($created->plainTextToken)
        ->patchJson(route('api.v1.webhook-endpoints.update', $endpoint), [
            'name' => 'Hijack',
            'url' => 'https://example.com/webhooks/zap',
            'event_type' => ZapEventType::MessageReceived->value,
            'payload_mode' => WebhookPayloadMode::Canonical->value,
        ])
        ->assertNotFound();

    expect($endpoint->fresh()?->name)->not->toBe('Hijack');
});

it('duplicates an endpoint with a new secret and the same map', function () {
    fakePublicWebhookDns();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value]);
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->custom()->create();
    $originalSecret = $endpoint->secret_encrypted;

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-endpoints.duplicate', $endpoint))
        ->assertCreated()
        ->assertJsonPath('data.payload_mode', 'custom');

    $copy = WebhookEndpoint::query()->where('id', '!=', $endpoint->id)->first();

    expect($copy)->not->toBeNull()
        ->and($copy?->secret_encrypted)->not->toBe($originalSecret)
        ->and($copy?->body_mapping)->toBe($endpoint->body_mapping)
        ->and($copy?->url)->toBe($endpoint->url);
});
