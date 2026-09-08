<?php

use App\Enums\ApiAbility;
use App\Enums\WebhookDeliveryStatus;
use App\Jobs\DeliverCustomerWebhook;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Queue;

it('lists deliveries for the current workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksRead->value]);
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();
    $event = WebhookEvent::factory()->for($owner->ownedWorkspace)->create();
    WebhookDelivery::factory()->for($owner->ownedWorkspace)->for($endpoint, 'endpoint')->for($event, 'event')->delivered()->create();

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.webhook-deliveries.index'))
        ->assertOk()
        ->assertJsonPath('data.0.status', WebhookDeliveryStatus::Delivered->value);
});

it('returns 404 when retrying another workspace delivery', function () {
    Queue::fake();

    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();
    $event = WebhookEvent::factory()->for($owner->ownedWorkspace)->create();
    $delivery = WebhookDelivery::factory()
        ->for($owner->ownedWorkspace)
        ->for($endpoint, 'endpoint')
        ->for($event, 'event')
        ->deadLetter()
        ->create();
    $created = createWorkspaceApiToken($stranger, [ApiAbility::WebhooksWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-deliveries.retry', $delivery))
        ->assertNotFound();
});

it('retries a dead-lettered delivery while the payload still exists', function () {
    Queue::fake();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::WebhooksWrite->value]);
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();
    $event = WebhookEvent::factory()->for($owner->ownedWorkspace)->create();
    $delivery = WebhookDelivery::factory()
        ->for($owner->ownedWorkspace)
        ->for($endpoint, 'endpoint')
        ->for($event, 'event')
        ->deadLetter()
        ->create();

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.webhook-deliveries.retry', $delivery))
        ->assertOk()
        ->assertJsonPath('data.status', 'pending');

    Queue::assertPushed(DeliverCustomerWebhook::class);
});
