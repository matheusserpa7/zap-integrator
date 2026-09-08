<?php

use App\Enums\InstanceStatus;
use App\Models\Instance;
use App\Models\User;

it('refuses to ingest fixtures outside e2e and testing', function () {
    $previous = app()->environment();
    app()['env'] = 'production';

    try {
        $this->artisan('e2e:ingest-provider', [
            'instance' => 'ins_missing',
            'fixture' => 'webhook-connection-update-open',
        ])->assertFailed();
    } finally {
        app()['env'] = $previous;
    }
});

it('marks an instance connected from a committed provider fixture', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();

    $this->artisan('e2e:ingest-provider', [
        'instance' => $instance->public_id,
        'fixture' => 'webhook-connection-update-open',
    ])->assertSuccessful();

    expect($instance->fresh()?->status)->toBe(InstanceStatus::Connected);
});
