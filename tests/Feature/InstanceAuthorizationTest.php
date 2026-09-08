<?php

use App\Enums\WorkspaceRole;
use App\Jobs\DeleteInstance;
use App\Jobs\DisconnectInstance;
use App\Models\Instance;
use App\Models\User;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\Queue;

it('returns 404 when another workspace member guesses an instance public id', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();
    $stranger = User::factory()->withWorkspace()->create();

    $this->actingAs($stranger)
        ->get(route('instances.show', $instance))
        ->assertNotFound();
});

it('does not resolve instances by sequential primary key', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create();

    $this->actingAs($owner)
        ->get('/instances/'.$instance->id)
        ->assertNotFound();
});

it('returns 404 when a stranger tries to delete another workspace instance', function () {
    Queue::fake([DeleteInstance::class]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create();
    $stranger = User::factory()->withWorkspace()->create();

    $this->actingAs($stranger)
        ->delete(route('instances.destroy', $instance))
        ->assertNotFound();

    Queue::assertNothingPushed();
    $this->assertModelExists($instance);
});

it('returns 404 when a stranger tries to disconnect another workspace instance', function () {
    Queue::fake([DisconnectInstance::class]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $stranger = User::factory()->withWorkspace()->create();

    $this->actingAs($stranger)
        ->post(route('instances.disconnect', $instance))
        ->assertNotFound();

    Queue::assertNothingPushed();
});

it('forbids a viewer from creating or deleting an instance', function () {
    Queue::fake([DeleteInstance::class]);

    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $viewer = User::factory()->create();
    WorkspaceMember::factory()->for($workspace)->for($viewer)->create([
        'role' => WorkspaceRole::Viewer,
    ]);
    $instance = Instance::factory()->for($workspace)->create();

    $this->actingAs($viewer)
        ->post(route('instances.store'), ['name' => 'Nova'])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->delete(route('instances.destroy', $instance))
        ->assertNotFound();

    Queue::assertNothingPushed();
});
