<?php

use App\Domain\Instances\MapProviderConnectionState;
use App\Enums\InstanceStatus;
use App\Enums\ProviderConnectionStatus;

it('maps a provider connected state onto the ZAP connected status', function () {
    $map = new MapProviderConnectionState;

    expect($map(InstanceStatus::WaitingQr, ProviderConnectionStatus::Connected))->toBe(InstanceStatus::Connected)
        ->and($map(InstanceStatus::Connecting, ProviderConnectionStatus::Connecting))->toBe(InstanceStatus::Connecting);
});

it('keeps waiting for a QR when the provider reports disconnected before a scan', function () {
    $map = new MapProviderConnectionState;

    expect($map(InstanceStatus::WaitingQr, ProviderConnectionStatus::Disconnected))->toBe(InstanceStatus::WaitingQr)
        ->and($map(InstanceStatus::Connected, ProviderConnectionStatus::Disconnected))->toBe(InstanceStatus::Disconnected);
});
