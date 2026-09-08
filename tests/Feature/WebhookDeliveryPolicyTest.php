<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;
use App\Models\WorkspaceMember;

it('allows an owner to retry a delivery', function () {
    $owner = User::factory()->withWorkspace()->create();
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();
    $event = WebhookEvent::factory()->for($owner->ownedWorkspace)->create();
    $delivery = WebhookDelivery::factory()
        ->for($owner->ownedWorkspace)
        ->for($endpoint, 'endpoint')
        ->for($event, 'event')
        ->create();

    expect($owner->can('view', $delivery))->toBeTrue()
        ->and($owner->can('retry', $delivery))->toBeTrue();
});

it('denies a viewer from retrying a delivery', function () {
    $owner = User::factory()->withWorkspace()->create();
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($owner->ownedWorkspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();
    $event = WebhookEvent::factory()->for($owner->ownedWorkspace)->create();
    $delivery = WebhookDelivery::factory()
        ->for($owner->ownedWorkspace)
        ->for($endpoint, 'endpoint')
        ->for($event, 'event')
        ->create();

    expect($viewer->can('view', $delivery))->toBeTrue()
        ->and($viewer->can('retry', $delivery))->toBeFalse();
});
