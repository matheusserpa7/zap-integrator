<?php

use App\Domain\Webhooks\ComputeProviderEventFingerprint;
use App\Enums\ZapEventType;
use App\Integrations\Evolution\NormalizeEvolutionWebhook;

it('maps Evolution event names onto ZAP event names', function (string $fixture, ZapEventType $expected) {
    $event = (new NormalizeEvolutionWebhook)(evolutionFixture($fixture));

    expect($event->eventType)->toBe($expected);
})->with([
    'QR update' => ['webhook-qrcode-updated', ZapEventType::InstanceQrUpdated],
    'connection open' => ['webhook-connection-update-open', ZapEventType::InstanceConnectionUpdated],
    'inbound upsert' => ['webhook-messages-upsert', ZapEventType::MessageReceived],
    'status update' => ['webhook-messages-update', ZapEventType::MessageUpdated],
    'send message' => ['webhook-send-message', ZapEventType::MessageSent],
]);

it('treats dotted Evolution event names as the same ZAP events', function () {
    $event = (new NormalizeEvolutionWebhook)([
        'event' => 'qrcode.updated',
        'data' => [
            'base64' => 'data:image/png;base64,QR_WEBHOOK_SECRET_DO_NOT_LOG',
        ],
    ]);

    expect($event->eventType)->toBe(ZapEventType::InstanceQrUpdated)
        ->and($event->providerEventType)->toBe('QRCODE_UPDATED')
        ->and($event->payload['qr_code'] ?? null)->toContain('QR_WEBHOOK_SECRET_DO_NOT_LOG');
});

it('maps outbound MESSAGES_UPSERT onto message.updated', function () {
    $payload = evolutionFixture('webhook-messages-upsert');
    $payload['data']['key']['fromMe'] = true;

    $event = (new NormalizeEvolutionWebhook)($payload);

    expect($event->eventType)->toBe(ZapEventType::MessageUpdated);
});

it('does not keep provider secrets in the slim payload', function () {
    $qr = (new NormalizeEvolutionWebhook)(evolutionFixture('webhook-qrcode-updated'));
    $message = (new NormalizeEvolutionWebhook)(evolutionFixture('webhook-messages-upsert'));

    expect($qr->payload)->not->toHaveKey('apikey')
        ->and($qr->canonicalPayload)->not->toContain('EVO_APIKEY_MUST_NOT_BE_STORED')
        ->and($message->payload['text'] ?? null)->toBe('MESSAGE_BODY_MUST_NOT_BE_STORED')
        ->and($message->payload['type'] ?? null)->toBe('text')
        ->and($message->payload)->not->toHaveKey('apikey');
});

it('prefers a stable provider message id when building the fingerprint', function () {
    $first = (new NormalizeEvolutionWebhook)(evolutionFixture('webhook-messages-upsert'));
    $secondPayload = evolutionFixture('webhook-messages-upsert');
    $secondPayload['date_time'] = '2026-09-07T18:00:00.000Z';
    $second = (new NormalizeEvolutionWebhook)($secondPayload);

    $fingerprint = new ComputeProviderEventFingerprint;

    expect($first->providerEventId)->toBe('ABCD1234EFGH')
        ->and($fingerprint(10, $first))->toBe($fingerprint(10, $second));
});

it('ignores unknown Evolution events without failing', function () {
    $event = (new NormalizeEvolutionWebhook)([
        'event' => 'LABELS_EDIT',
        'data' => ['id' => 'ignored'],
    ]);

    expect($event->eventType)->toBe(ZapEventType::Ignored);
});
