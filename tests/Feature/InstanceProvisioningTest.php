<?php

use App\Enums\AuditAction;
use App\Enums\InstanceStatus;
use App\Jobs\DeleteInstance;
use App\Jobs\DisconnectInstance;
use App\Jobs\ProvisionInstance;
use App\Models\AuditLog;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

it('creates an instance asynchronously and redirects to the show page', function () {
    Queue::fake([ProvisionInstance::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();

    $this->actingAs($owner)
        ->post(route('instances.store'), ['name' => 'Atendimento'])
        ->assertRedirect();

    $instance = Instance::query()->first();

    expect($instance)->not->toBeNull()
        ->and($instance?->status)->toBe(InstanceStatus::Creating)
        ->and($instance?->name)->toBe('Atendimento')
        ->and($instance?->provider_instance_name)->toStartWith('zap_'.$owner->ownedWorkspace?->public_id.'_')
        ->and($instance?->provider_instance_name)->not->toContain('Atendimento');

    Queue::assertPushed(ProvisionInstance::class, fn (ProvisionInstance $job): bool => $job->instanceId === $instance?->id);
    Http::assertNothingSent();

    expect(AuditLog::query()->where('action', AuditAction::InstanceCreated)->count())->toBe(1);

    $this->actingAs($owner)
        ->get(route('instances.show', $instance))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Instances/Show')
            ->where('instance.name', 'Atendimento')
            ->where('instance.status', 'creating')
            ->missing('instance.provider_instance_name')
            ->missing('instance.provider_instance_token_encrypted')
            ->missing('instance.provider_webhook_secret_encrypted'));
});

it('rejects a second instance when the workspace quota is exceeded', function () {
    Queue::fake([ProvisionInstance::class]);

    $owner = User::factory()->withWorkspace()->create();
    Instance::factory()->for($owner->ownedWorkspace)->create();

    $this->actingAs($owner)
        ->from(route('instances.create'))
        ->post(route('instances.store'), ['name' => 'Segunda'])
        ->assertRedirect(route('instances.create'))
        ->assertSessionHasErrors(['name' => 'Este workspace já atingiu o limite de instâncias.']);

    expect(Instance::query()->count())->toBe(1);
    Queue::assertNotPushed(ProvisionInstance::class);
});

it('validates that the instance name is required', function () {
    Queue::fake([ProvisionInstance::class]);

    $owner = User::factory()->withWorkspace()->create();

    $this->actingAs($owner)
        ->from(route('instances.create'))
        ->post(route('instances.store'), ['name' => ''])
        ->assertRedirect(route('instances.create'))
        ->assertSessionHasErrors(['name' => 'Informe um nome para a instância.']);

    Queue::assertNothingPushed();
});

it('redirects guests away from instance creation', function () {
    $this->post(route('instances.store'), ['name' => 'Atendimento'])
        ->assertRedirect(route('login'));
});

it('queues disconnect and delete without calling the provider on the HTTP request', function () {
    Queue::fake([DisconnectInstance::class, DeleteInstance::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();

    $this->actingAs($owner)
        ->post(route('instances.disconnect', $instance))
        ->assertRedirect();

    Queue::assertPushed(DisconnectInstance::class, fn (DisconnectInstance $job): bool => $job->instanceId === $instance->id);
    Http::assertNothingSent();

    $this->actingAs($owner)
        ->delete(route('instances.destroy', $instance))
        ->assertRedirect(route('instances.index'));

    Queue::assertPushed(DeleteInstance::class, fn (DeleteInstance $job): bool => $job->instanceId === $instance->id);
    expect($instance->fresh()?->status)->toBe(InstanceStatus::Deleting);
});

it('links the dashboard CTA to instance creation when the quota is open', function () {
    $owner = User::factory()->withWorkspace()->create();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('canCreateInstance', true)
            ->where('instanceCount', 0));
});
