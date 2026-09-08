<?php

use App\Domain\Webhooks\RenderWebhookPayload;
use App\Domain\Webhooks\ValidateWebhookMapping;
use App\Domain\Webhooks\WebhookEventCatalog;
use App\Enums\WebhookPayloadMode;
use App\Enums\ZapEventType;
use App\Models\WebhookEndpoint;

it('returns the canonical envelope without mapping', function () {
    $catalog = new WebhookEventCatalog;
    $render = new RenderWebhookPayload($catalog, new ValidateWebhookMapping($catalog));
    $canonical = $catalog->fixture(ZapEventType::MessageReceived);
    $endpoint = new WebhookEndpoint([
        'event_type' => ZapEventType::MessageReceived,
        'payload_mode' => WebhookPayloadMode::Canonical,
        'body_mapping' => [],
    ]);

    expect($render->body($endpoint, $canonical))->toBe($canonical);
});

it('projects custom rows and uses null for missing runtime values', function () {
    $catalog = new WebhookEventCatalog;
    $render = new RenderWebhookPayload($catalog, new ValidateWebhookMapping($catalog));
    $canonical = $catalog->fixture(ZapEventType::MessageReceived);
    unset($canonical['data']['text']);

    $endpoint = new WebhookEndpoint([
        'event_type' => ZapEventType::MessageReceived,
        'payload_mode' => WebhookPayloadMode::Custom,
        'body_mapping' => [
            ['path' => 'customer.name', 'value_mode' => 'field', 'value' => 'contact.name'],
            ['path' => 'message.text', 'value_mode' => 'field', 'value' => 'message.text'],
            ['path' => 'source', 'value_mode' => 'fixed', 'value' => 'zap'],
            ['path' => 'reference', 'value_mode' => 'expression', 'value' => 'zap-{{ message.id }}'],
        ],
    ]);

    expect($render->body($endpoint, $canonical))->toBe([
        'customer' => ['name' => 'Ana Ribeiro'],
        'message' => ['text' => null],
        'source' => 'zap',
        'reference' => 'zap-msg_019fixture000000000000000001',
    ]);
});
