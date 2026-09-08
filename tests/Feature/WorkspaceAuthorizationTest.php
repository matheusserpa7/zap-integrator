<?php

use App\Models\User;

it('lets a workspace owner open their workspace by public id', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;

    $this->actingAs($owner)
        ->get(route('workspaces.show', $workspace))
        ->assertRedirect(route('dashboard'));
});

it('returns 404 when another member guesses a workspace public id', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();

    $this->actingAs($stranger)
        ->get(route('workspaces.show', $owner->ownedWorkspace))
        ->assertNotFound();
});

it('returns 404 when a platform admin guesses another workspace public id', function () {
    $owner = User::factory()->withWorkspace()->create();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->get(route('workspaces.show', $owner->ownedWorkspace))
        ->assertNotFound();
});

it('reads the default instance quota from configuration', function () {
    $owner = User::factory()->withWorkspace()->create();

    expect($owner->ownedWorkspace?->max_instances)->toBeNull()
        ->and($owner->ownedWorkspace?->maxInstancesLimit())->toBe(1)
        ->and(config('zap.quotas.max_instances_per_workspace'))->toBe(1);
});

it('does not resolve workspaces by sequential primary key', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;

    $this->actingAs($owner)
        ->get('/workspaces/'.$workspace->id)
        ->assertNotFound();
});
