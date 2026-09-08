<?php

use App\Models\User;

it('allows a member to view their workspace', function () {
    $owner = User::factory()->withWorkspace()->create();

    expect($owner->can('view', $owner->ownedWorkspace))->toBeTrue();
});

it('denies a platform admin who is not a member of the workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $admin = User::factory()->platformAdmin()->create();

    expect($admin->can('view', $owner->ownedWorkspace))->toBeFalse();
});

it('denies a user from another workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();

    expect($stranger->can('view', $owner->ownedWorkspace))->toBeFalse();
});
