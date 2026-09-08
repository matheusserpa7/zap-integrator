<?php

use App\Enums\WorkspaceRole;
use App\Models\Instance;
use App\Models\User;
use App\Models\WorkspaceMember;

it('allows an owner to create, view, disconnect, and delete an instance', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->create();

    expect($owner->can('viewAny', Instance::class))->toBeTrue()
        ->and($owner->can('create', [Instance::class, $workspace]))->toBeTrue()
        ->and($owner->can('view', $instance))->toBeTrue()
        ->and($owner->can('refreshQr', $instance))->toBeTrue()
        ->and($owner->can('disconnect', $instance))->toBeTrue()
        ->and($owner->can('delete', $instance))->toBeTrue();
});

it('allows a viewer to see QR status but not mutate the instance', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);
    $instance = Instance::factory()->for($workspace)->create();

    expect($viewer->can('view', $instance))->toBeTrue()
        ->and($viewer->can('refreshQr', $instance))->toBeTrue()
        ->and($viewer->can('create', [Instance::class, $workspace]))->toBeFalse()
        ->and($viewer->can('disconnect', $instance))->toBeFalse()
        ->and($viewer->can('delete', $instance))->toBeFalse();
});

it('denies a user from another workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create();

    expect($stranger->can('view', $instance))->toBeFalse()
        ->and($stranger->can('create', [Instance::class, $owner->ownedWorkspace]))->toBeFalse()
        ->and($stranger->can('delete', $instance))->toBeFalse();
});
