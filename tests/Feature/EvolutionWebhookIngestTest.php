<?php

use App\Enums\InstanceStatus;
use App\Enums\ZapEventType;
use App\Events\InstanceConnectionChanged;
use App\Events\InstanceQrUpdated;
use App\Jobs\ProcessProviderWebhook;
use App\Models\Instance;
use App\Models\ProviderEventFingerprint;
use App\Models\User;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

it('rejects a forged webhook secret with 401', function () {
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-qrcode-updated'), 'forged-secret')
        ->assertUnauthorized();

    expect(ProviderEventFingerprint::query()->count())->toBe(0)
        ->and($instance->fresh()?->status)->toBe(InstanceStatus::WaitingQr);

    Http::assertNothingSent();
});

it('returns 404 when the instance public id is unknown', function () {
    Http::preventStrayRequests();

    $this->postJson(
        route('internal.webhooks.evolution', 'ins_does_not_exist'),
        evolutionFixture('webhook-qrcode-updated'),
        ['X-ZAP-Evolution-Secret' => 'anything'],
    )->assertNotFound();

    Http::assertNothingSent();
});

it('returns 413 when the payload exceeds the configured maximum', function () {
    Http::preventStrayRequests();
    config(['zap.webhooks.max_payload_bytes' => 32]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-qrcode-updated'))
        ->assertStatus(413);

    expect(ProviderEventFingerprint::query()->count())->toBe(0);
    Http::assertNothingSent();
});

it('accepts a webhook with 202 without calling Evolution', function () {
    Queue::fake([ProcessProviderWebhook::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-qrcode-updated'))
        ->assertAccepted();

    Queue::assertPushedOn(
        'critical',
        ProcessProviderWebhook::class,
        fn (ProcessProviderWebhook $job): bool => $job->instanceId === $instance->id,
    );
    Http::assertNothingSent();
    expect($instance->fresh()?->status)->toBe(InstanceStatus::WaitingQr);
});

it('updates instance QR state from a QRCODE_UPDATED webhook', function () {
    Event::fake([InstanceQrUpdated::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create([
        'status' => InstanceStatus::Creating,
        'qr_code_encrypted' => null,
    ]);

    postEvolutionWebhook($instance, evolutionFixture('webhook-qrcode-updated'))
        ->assertAccepted();

    $instance->refresh();

    expect($instance->status)->toBe(InstanceStatus::WaitingQr)
        ->and($instance->qrCodeForClient())->toContain('QR_WEBHOOK_SECRET_DO_NOT_LOG');

    Event::assertDispatched(InstanceQrUpdated::class, function (InstanceQrUpdated $event) use ($instance): bool {
        return $event->instancePublicId === $instance->public_id
            && $event->workspacePublicId === $instance->workspace->public_id
            && $event->status === InstanceStatus::WaitingQr->value;
    });

    expect(ProviderEventFingerprint::query()->where('instance_id', $instance->id)->first()?->payload)
        ->not->toHaveKey('qr_code');
});

it('does not process a duplicate webhook a second time', function () {
    Event::fake([InstanceQrUpdated::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create([
        'status' => InstanceStatus::Creating,
    ]);
    $payload = evolutionFixture('webhook-qrcode-updated');

    postEvolutionWebhook($instance, $payload)->assertAccepted();
    postEvolutionWebhook($instance, $payload)->assertAccepted();

    expect(ProviderEventFingerprint::query()->where('instance_id', $instance->id)->count())->toBe(1);
    Event::assertDispatchedTimes(InstanceQrUpdated::class, 1);
});

it('marks an instance connected from a CONNECTION_UPDATE webhook', function () {
    Event::fake([InstanceConnectionChanged::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->waitingQr()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-connection-update-open'))
        ->assertAccepted();

    $instance->refresh();

    expect($instance->status)->toBe(InstanceStatus::Connected)
        ->and($instance->phone_number)->toBe('5511999999999')
        ->and($instance->qr_code_encrypted)->toBeNull();

    Event::assertDispatched(InstanceConnectionChanged::class, function (InstanceConnectionChanged $event) use ($instance): bool {
        return $event->instancePublicId === $instance->public_id
            && $event->status === InstanceStatus::Connected->value
            && $event->phoneNumber === '5511999999999';
    });
});

it('marks a connected instance disconnected from a CONNECTION_UPDATE close webhook', function () {
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-connection-update-close'))
        ->assertAccepted();

    expect($instance->fresh()?->status)->toBe(InstanceStatus::Disconnected);
});

it('creates inbox rows from inbound text without keeping Evolution secrets in the fingerprint', function () {
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-messages-upsert'))
        ->assertAccepted();

    $fingerprint = ProviderEventFingerprint::query()->where('instance_id', $instance->id)->first();

    expect($fingerprint?->event_type)->toBe(ZapEventType::MessageReceived)
        ->and($fingerprint?->provider_event_id)->toBe('ABCD1234EFGH')
        ->and($fingerprint?->payload)->not->toHaveKey('apikey')
        ->and(json_encode($fingerprint?->payload))->not->toContain('EVO_APIKEY_MUST_NOT_BE_STORED')
        ->and($instance->fresh()?->status)->toBe(InstanceStatus::Connected);

    Http::assertNothingSent();
});

it('does not write QR values, webhook secrets, or provider keys to application logs', function () {
    Http::preventStrayRequests();
    $logs = [];
    Log::listen(function (MessageLogged $message) use (&$logs): void {
        $logs[] = json_encode([$message->message, $message->context], JSON_THROW_ON_ERROR);
    });

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create([
        'status' => InstanceStatus::Creating,
    ]);

    postEvolutionWebhook($instance, evolutionFixture('webhook-qrcode-updated'))
        ->assertAccepted();

    expect($logs)->not->toBeEmpty();

    foreach ($logs as $entry) {
        expect($entry)
            ->not->toContain('QR_WEBHOOK_SECRET_DO_NOT_LOG')
            ->and($entry)->not->toContain('EVO_APIKEY_MUST_NOT_BE_STORED')
            ->and($entry)->not->toContain((string) $instance->provider_webhook_secret_encrypted);
    }
});
