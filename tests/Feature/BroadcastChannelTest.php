<?php

use App\Broadcasting\WorkspaceChannel;
use App\Models\Instance;
use App\Models\User;

it('authorizes workspace members on the private workspace channel', function () {
    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;

    expect((new WorkspaceChannel)->join($owner, (string) $workspace?->public_id))->toBeTrue();
});

it('rejects a stranger from another workspace channel', function () {
    $owner = User::factory()->withWorkspace()->create();
    $stranger = User::factory()->withWorkspace()->create();

    expect((new WorkspaceChannel)->join($stranger, (string) $owner->ownedWorkspace?->public_id))->toBeFalse();
});

it('does not include provider tokens in instance show props after a QR is stored', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();

    $this->actingAs($owner)
        ->get(route('instances.show', $instance))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('instance.provider_instance_name')
            ->missing('instance.provider_instance_token_encrypted')
            ->where('instance.qr_code', $instance->qrCodeForClient()));
});
