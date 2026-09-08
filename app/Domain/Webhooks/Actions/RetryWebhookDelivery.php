<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Domain\Webhooks\Exceptions\WebhookPayloadExpired;
use App\Enums\WebhookDeliveryStatus;
use App\Jobs\DeliverCustomerWebhook;
use App\Models\WebhookDelivery;

final class RetryWebhookDelivery
{
    public function __invoke(WebhookDelivery $delivery): WebhookDelivery
    {
        $delivery->loadMissing('event');

        if ($delivery->event->expires_at->isPast()) {
            throw new WebhookPayloadExpired;
        }

        $delivery->forceFill([
            'status' => WebhookDeliveryStatus::Pending,
            'next_retry_at' => null,
        ])->save();

        DeliverCustomerWebhook::dispatch($delivery->id, $delivery->attempt + 1);

        return $delivery;
    }
}
