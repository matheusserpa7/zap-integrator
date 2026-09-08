<?php

use App\Enums\ApiAbility;
use App\Enums\WorkspaceRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\WorkspaceMember;

it('allows an owner to view and create api tokens for their workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;

    expect($owner->can('viewAny', [PersonalAccessToken::class, $workspace]))->toBeTrue()
        ->and($owner->can('create', [PersonalAccessToken::class, $workspace]))->toBeTrue();
});

it('allows an owner to revoke a workspace token', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner);

    expect($owner->can('delete', $created->token))->toBeTrue();
});

it('denies a viewer from creating or revoking api tokens', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);
    $created = createWorkspaceApiToken($owner);

    expect($viewer->can('viewAny', [PersonalAccessToken::class, $workspace]))->toBeFalse()
        ->and($viewer->can('create', [PersonalAccessToken::class, $workspace]))->toBeFalse()
        ->and($viewer->can('delete', $created->token))->toBeFalse();
});

it('denies a user from another workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::InstancesRead->value]);

    expect($stranger->can('viewAny', [PersonalAccessToken::class, $owner->ownedWorkspace]))->toBeFalse()
        ->and($stranger->can('create', [PersonalAccessToken::class, $owner->ownedWorkspace]))->toBeFalse()
        ->and($stranger->can('delete', $created->token))->toBeFalse();
});
