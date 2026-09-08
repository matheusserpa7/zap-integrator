<?php

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\WorkspaceMember;

it('allows an owner to manage webhook endpoints', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $endpoint = WebhookEndpoint::factory()->for($workspace)->create();

    expect($owner->can('viewAny', WebhookEndpoint::class))->toBeTrue()
        ->and($owner->can('create', [WebhookEndpoint::class, $workspace]))->toBeTrue()
        ->and($owner->can('view', $endpoint))->toBeTrue()
        ->and($owner->can('update', $endpoint))->toBeTrue()
        ->and($owner->can('delete', $endpoint))->toBeTrue()
        ->and($owner->can('test', $endpoint))->toBeTrue()
        ->and($owner->can('duplicate', $endpoint))->toBeTrue();
});

it('allows a viewer to see endpoints but not mutate them', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);
    $endpoint = WebhookEndpoint::factory()->for($workspace)->create();

    expect($viewer->can('view', $endpoint))->toBeTrue()
        ->and($viewer->can('create', [WebhookEndpoint::class, $workspace]))->toBeFalse()
        ->and($viewer->can('update', $endpoint))->toBeFalse()
        ->and($viewer->can('test', $endpoint))->toBeFalse();
});

it('denies a user from another workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();
    $endpoint = WebhookEndpoint::factory()->for($owner->ownedWorkspace)->create();

    expect($stranger->can('view', $endpoint))->toBeFalse()
        ->and($stranger->can('update', $endpoint))->toBeFalse();
});
