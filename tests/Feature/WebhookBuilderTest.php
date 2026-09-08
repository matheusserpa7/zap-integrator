<?php

use App\Enums\WebhookPayloadMode;
use App\Enums\WorkspaceRole;
use App\Enums\ZapEventType;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\WorkspaceMember;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the webhook index for a workspace owner', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('webhooks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Webhooks/Index')
            ->has('endpoints', 0)
            ->where('canCreate', true));
});

it('renders the three-column webhook builder', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('webhooks.builder'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Webhooks/Builder')
            ->where('endpoint', null)
            ->has('catalog')
            ->has('eventOptions')
            ->where('canEdit', true));
});

it('saves a webhook from the builder without sending a test request', function () {
    fakePublicWebhookDns();

    $owner = User::factory()->withWorkspace()->create();

    $this->actingAs($owner)
        ->post(route('webhooks.store'), [
            'name' => 'CRM',
            'url' => 'https://example.com/webhooks/zap',
            'event_type' => ZapEventType::MessageReceived->value,
            'payload_mode' => WebhookPayloadMode::Canonical->value,
            'enabled' => true,
        ])
        ->assertRedirect();

    $endpoint = WebhookEndpoint::query()->first();

    expect($endpoint)->not->toBeNull()
        ->and($endpoint?->payload_mode)->toBe(WebhookPayloadMode::Canonical)
        ->and(session('webhookSecret'))->toBeString();
});

it('forbids a viewer from creating a webhook', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);

    $this->actingAs($viewer)
        ->get(route('webhooks.builder'))
        ->assertForbidden();
});
