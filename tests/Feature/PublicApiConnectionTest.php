<?php

use App\Enums\ApiAbility;
use App\Jobs\RefreshInstanceQr;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('returns connection state for a token with instances:read', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::InstancesRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.connection', $instance))
        ->assertOk()
        ->assertJsonPath('data.id', $instance->public_id)
        ->assertJsonPath('data.status', 'connected')
        ->assertJsonPath('data.phone_number', $instance->phone_number)
        ->assertJsonMissingPath('data.provider_instance_token_encrypted');
});

it('refreshes the qr code asynchronously', function () {
    Queue::fake([RefreshInstanceQr::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::InstancesWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.instances.qr.refresh', $instance))
        ->assertAccepted()
        ->assertJsonPath('data.id', $instance->public_id);

    Queue::assertPushedOn('provider', RefreshInstanceQr::class);
    Http::assertNothingSent();
});

it('returns 422 when refreshing qr on a connected instance', function () {
    Queue::fake([RefreshInstanceQr::class]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::InstancesWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.instances.qr.refresh', $instance))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'instance_already_connected');

    Queue::assertNothingPushed();
});
