<?php

use App\Models\Conversation;
use App\Models\Instance;
use App\Models\User;

it('allows a workspace member to view a conversation', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create();
    $conversation = Conversation::factory()->for($instance)->create();

    expect($owner->can('viewAny', Conversation::class))->toBeTrue()
        ->and($owner->can('view', $conversation))->toBeTrue();
});

it('denies a user from another workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create();
    $conversation = Conversation::factory()->for($instance)->create();

    expect($stranger->can('view', $conversation))->toBeFalse();
});
