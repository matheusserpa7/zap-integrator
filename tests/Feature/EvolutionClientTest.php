<?php

use App\Domain\Messaging\Data\DownloadMediaData;
use App\Domain\Messaging\Data\SendTextData;
use App\Domain\Messaging\Exceptions\ProviderAuthenticationFailed;
use App\Enums\InstanceStatus;
use App\Enums\ProviderConnectionStatus;
use App\Events\InstanceQrUpdated;
use App\Integrations\Evolution\EvolutionClient;
use App\Integrations\Evolution\EvolutionMessagingProvider;
use App\Integrations\Evolution\Exceptions\EvolutionApiException;
use App\Jobs\DeleteInstance;
use App\Jobs\DisconnectInstance;
use App\Jobs\ProvisionInstance;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

it('creates an instance through the Evolution HTTP client using fixtures', function () {
    Http::preventStrayRequests();
    Http::fake([
        'http://evolution.test/instance/create' => Http::response(evolutionFixture('create-instance'), 201),
    ]);

    $payload = app(EvolutionClient::class)->createInstance('zap_ws_test_ins_test', 'token');

    expect($payload['hash']['apikey'] ?? null)->toBe('evo_instance_token_secret');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://evolution.test/instance/create'
        && $request->hasHeader('apikey', 'test-evolution-key')
        && $request->hasHeader('X-Correlation-Id')
        && $request['instanceName'] === 'zap_ws_test_ins_test');
});

it('retries safe Evolution GET operations after a server error', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fake([
        'http://evolution.test/instance/connect/*' => Http::sequence()
            ->push(['error' => 'temporary'], 500)
            ->push(evolutionFixture('connect-qr'), 200),
    ]);

    $payload = app(EvolutionClient::class)->connect('zap_ws_test_ins_test');

    expect($payload['base64'] ?? null)->toContain('QR_SECRET_FIXTURE_VALUE_DO_NOT_LOG');
    Http::assertSentCount(2);
});

it('does not write QR values or provider secrets to application logs', function () {
    Http::preventStrayRequests();
    $logs = [];
    Log::listen(function (MessageLogged $message) use (&$logs): void {
        $logs[] = json_encode([$message->message, $message->context], JSON_THROW_ON_ERROR);
    });

    Http::fake([
        'http://evolution.test/instance/connect/*' => Http::response(evolutionFixture('connect-qr'), 200),
    ]);

    app(EvolutionClient::class)->connect('zap_ws_test_ins_test');

    expect($logs)->not->toBeEmpty();

    foreach ($logs as $entry) {
        expect($entry)
            ->not->toContain('QR_SECRET_FIXTURE_VALUE_DO_NOT_LOG')
            ->and($entry)->not->toContain('test-evolution-key')
            ->and($entry)->not->toContain('apikey');
    }
});

it('maps provider connection states into ZAP statuses', function () {
    Http::preventStrayRequests();
    Http::fake([
        'http://evolution.test/instance/connectionState/*' => Http::response(evolutionFixture('connection-state-open'), 200),
    ]);

    $state = app(EvolutionMessagingProvider::class)->getConnectionState('zap_ws_test_ins_test');

    expect($state->status)->toBe(ProviderConnectionStatus::Connected)
        ->and($state->phoneNumber)->toBe('5511999999999');
});

it('maps Evolution 401 responses to a provider authentication failure', function () {
    Http::preventStrayRequests();
    Http::fake([
        'http://evolution.test/instance/connect/*' => Http::response(evolutionFixture('unauthorized'), 401),
    ]);

    expect(fn () => app(EvolutionMessagingProvider::class)->connectInstance('zap_ws_test_ins_test'))
        ->toThrow(ProviderAuthenticationFailed::class);
});

it('sends text and downloads media through Evolution HTTP fakes', function () {
    Http::preventStrayRequests();
    Http::fake([
        'http://evolution.test/message/sendText/*' => Http::response(evolutionFixture('send-text'), 200),
        'http://evolution.test/chat/getBase64FromMediaMessage/*' => Http::response(evolutionFixture('get-base64-media'), 200),
    ]);

    $provider = app(EvolutionMessagingProvider::class);
    $sent = $provider->sendText(new SendTextData('zap_x', '5511999999999', 'oi'));
    $media = $provider->downloadMedia(new DownloadMediaData('zap_x', 'IMG1'));

    expect($sent->providerMessageId)->toBe('EVO_SENT_TEXT_1')
        ->and($media->mimeType)->toBe('image/jpeg')
        ->and($media->contents)->toBe('hello-media');
});

it('provisions an instance against HTTP fakes and stores a waiting QR without logging it', function () {
    Http::preventStrayRequests();
    Event::fake([InstanceQrUpdated::class]);
    $logs = [];
    Log::listen(function (MessageLogged $message) use (&$logs): void {
        $logs[] = json_encode([$message->message, $message->context], JSON_THROW_ON_ERROR);
    });

    Http::fake([
        'http://evolution.test/instance/create' => Http::response(evolutionFixture('create-instance'), 201),
        'http://evolution.test/webhook/set/*' => Http::response(evolutionFixture('webhook-set'), 200),
        'http://evolution.test/instance/connect/*' => Http::response(evolutionFixture('connect-qr'), 200),
    ]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create([
        'status' => InstanceStatus::Creating,
    ]);

    $job = new ProvisionInstance($instance->id);
    $this->app->call([$job, 'handle']);

    $instance->refresh();

    expect($instance->status)->toBe(InstanceStatus::WaitingQr)
        ->and($instance->qrCodeForClient())->toContain('QR_SECRET_FIXTURE_VALUE_DO_NOT_LOG');

    Event::assertDispatched(InstanceQrUpdated::class, function (InstanceQrUpdated $event) use ($instance): bool {
        return $event->instancePublicId === $instance->public_id
            && $event->workspacePublicId === $instance->workspace->public_id
            && is_string($event->qrCode);
    });

    foreach ($logs as $entry) {
        expect($entry)->not->toContain('QR_SECRET_FIXTURE_VALUE_DO_NOT_LOG');
    }

    $this->actingAs($owner)
        ->get(route('instances.show', $instance))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('instance.status', 'waiting_qr')
            ->where('instance.qr_code', $instance->qrCodeForClient())
            ->missing('instance.provider_instance_token_encrypted'));
});

it('disconnects and deletes instances through the provider without blocking the HTTP request', function () {
    Http::preventStrayRequests();
    Http::fake([
        'http://evolution.test/instance/logout/*' => Http::response(evolutionFixture('logout'), 200),
        'http://evolution.test/instance/delete/*' => Http::response(evolutionFixture('delete-instance'), 200),
    ]);

    $owner = User::factory()->withWorkspace()->create();
    $connected = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();

    $this->app->call([(new DisconnectInstance($connected->id, $owner->id)), 'handle']);
    $connected->refresh();
    expect($connected->status)->toBe(InstanceStatus::Disconnected)
        ->and($connected->qr_code_encrypted)->toBeNull();

    $deleting = Instance::factory()->for($owner->ownedWorkspace)->create([
        'status' => InstanceStatus::Deleting,
    ]);
    $this->app->call([(new DeleteInstance($deleting->id, $owner->id)), 'handle']);
    expect(Instance::query()->whereKey($deleting->id)->exists())->toBeFalse();
});

it('throws a structured exception when Evolution is unreachable', function () {
    Http::preventStrayRequests();
    Http::fake([
        'http://evolution.test/instance/create' => Http::failedConnection(),
    ]);

    expect(fn () => app(EvolutionClient::class)->createInstance('zap_ws_test_ins_test', 'token'))
        ->toThrow(EvolutionApiException::class);
});
