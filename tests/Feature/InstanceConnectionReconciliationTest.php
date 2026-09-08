<?php

use App\Enums\InstanceStatus;
use App\Events\InstanceConnectionChanged;
use App\Jobs\SyncInstanceConnection;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('repairs a stale disconnected instance when Evolution reports open', function () {
    Event::fake([InstanceConnectionChanged::class]);
    Http::preventStrayRequests();
    Http::fake([
        'http://evolution.test/instance/connectionState/*' => Http::response(evolutionFixture('connection-state-open'), 200),
    ]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->disconnected()->create([
        'last_seen_at' => now()->subMinutes(10),
    ]);

    $this->artisan('instances:reconcile-connections')
        ->assertSuccessful();

    $instance->refresh();

    expect($instance->status)->toBe(InstanceStatus::Connected)
        ->and($instance->phone_number)->toBe('5511999999999');

    Event::assertDispatched(InstanceConnectionChanged::class, function (InstanceConnectionChanged $event) use ($instance): bool {
        return $event->instancePublicId === $instance->public_id
            && $event->status === InstanceStatus::Connected->value;
    });
});

it('does not call Evolution for instances that are still creating', function () {
    Queue::fake([SyncInstanceConnection::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    Instance::factory()->for($owner->ownedWorkspace)->create([
        'status' => InstanceStatus::Creating,
        'last_seen_at' => now(),
    ]);

    $this->artisan('instances:reconcile-connections')
        ->assertSuccessful();

    Queue::assertNothingPushed();
    Http::assertNothingSent();
});
