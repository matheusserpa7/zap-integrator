<?php

use App\Domain\Webhooks\Exceptions\WebhookMappingInvalid;
use App\Domain\Webhooks\ValidateWebhookMapping;
use App\Domain\Webhooks\WebhookEventCatalog;
use App\Enums\ZapEventType;

it('accepts allowlisted field paths for the event type', function () {
    $validate = new ValidateWebhookMapping(new WebhookEventCatalog);

    $validate(ZapEventType::MessageReceived, [
        ['path' => 'customer.phone', 'value_mode' => 'field', 'value' => 'data.from'],
        ['path' => 'source', 'value_mode' => 'fixed', 'value' => 'zap'],
        ['path' => 'reference', 'value_mode' => 'expression', 'value' => 'zap-{{ message.id }}'],
    ]);

    expect(true)->toBeTrue();
});

it('rejects unknown field paths', function () {
    $validate = new ValidateWebhookMapping(new WebhookEventCatalog);

    expect(fn () => $validate(ZapEventType::MessageReceived, [
        ['path' => 'oops', 'value_mode' => 'field', 'value' => 'data.unknown'],
    ]))->toThrow(WebhookMappingInvalid::class);
});

it('rejects javascript-like expressions', function () {
    $validate = new ValidateWebhookMapping(new WebhookEventCatalog);

    expect(fn () => $validate(ZapEventType::MessageReceived, [
        ['path' => 'oops', 'value_mode' => 'expression', 'value' => '{{ message.id | upper }}'],
    ]))->toThrow(WebhookMappingInvalid::class);
});
