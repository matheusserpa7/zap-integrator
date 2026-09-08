<?php

use App\Enums\InstanceStatus;
use App\Models\Instance;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use Inertia\Testing\AssertableInertia as Assert;

it('renders real workspace counters on the dashboard', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->connected()->create();
    $endpoint = WebhookEndpoint::factory()->for($workspace)->create();
    $deliveredEvent = WebhookEvent::factory()->for($workspace)->for($instance)->create();
    $retryEvent = WebhookEvent::factory()->for($workspace)->for($instance)->create();
    $deadLetterEvent = WebhookEvent::factory()->for($workspace)->for($instance)->create();

    WebhookDelivery::factory()->for($workspace)->for($endpoint, 'endpoint')->for($deliveredEvent, 'event')->delivered()->create();
    WebhookDelivery::factory()->for($workspace)->for($endpoint, 'endpoint')->for($retryEvent, 'event')->retrying()->create();
    WebhookDelivery::factory()->for($workspace)->for($endpoint, 'endpoint')->for($deadLetterEvent, 'event')->deadLetter()->create();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('instanceCount', 1)
            ->where('connectedCount', 1)
            ->where('webhookDeliveredCount', 1)
            ->where('webhookRetryingCount', 1)
            ->where('webhookDeadLetterCount', 1)
            ->where('queueDepth', null)
            ->where('canCreateInstance', false)
            ->where('primaryInstanceUrl', route('instances.show', $instance)));
});

it('does not invent an availability percentage', function () {
    $this->actingAs(User::factory()->withWorkspace()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('instanceCount', 0)
            ->where('connectedCount', 0)
            ->missing('availability')
            ->missing('availabilityPercent'))
        ->assertDontSee('disponibilidade', false);
});

it('counts only connected instances toward the connected metric', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;

    Instance::factory()->for($workspace)->connected()->create();
    Instance::factory()->for($workspace)->create(['status' => InstanceStatus::Disconnected]);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('instanceCount', 2)
            ->where('connectedCount', 1));
});
