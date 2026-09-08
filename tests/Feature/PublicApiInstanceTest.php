<?php

use App\Enums\ApiAbility;
use App\Jobs\ProvisionInstance;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('lists workspace instances for a token with instances:read', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create(['name' => 'Atendimento']);
    $created = createWorkspaceApiToken($owner);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $instance->public_id)
        ->assertJsonPath('data.0.name', 'Atendimento')
        ->assertJsonMissingPath('data.0.provider_instance_name')
        ->assertJsonMissingPath('data.0.provider_instance_token_encrypted');
});

it('returns 404 when another workspace instance public id is guessed', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create();
    $stranger = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($stranger);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.instances.show', $instance))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');
});

it('creates an instance asynchronously when the token has instances:write', function () {
    Queue::fake([ProvisionInstance::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [
        ApiAbility::InstancesRead->value,
        ApiAbility::InstancesWrite->value,
    ]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.instances.store'), ['name' => 'API'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'API')
        ->assertJsonPath('data.status', 'creating')
        ->assertJsonMissingPath('data.provider_instance_token_encrypted');

    $instance = Instance::query()->first();

    expect($instance)->not->toBeNull();
    Queue::assertPushed(ProvisionInstance::class, fn (ProvisionInstance $job): bool => $job->instanceId === $instance?->id);
    Http::assertNothingSent();
});

it('returns 422 when instance quota is exceeded via the api', function () {
    Queue::fake([ProvisionInstance::class]);

    $owner = User::factory()->withWorkspace()->create();
    Instance::factory()->for($owner->ownedWorkspace)->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::InstancesWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.instances.store'), ['name' => 'Segunda'])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'instance_quota_exceeded');

    expect(Instance::query()->count())->toBe(1);
    Queue::assertNothingPushed();
});

it('returns 422 when the instance name is missing', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::InstancesWrite->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.instances.store'), ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_error')
        ->assertJsonPath('error.message', 'The instance name is required.');
});
