<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Enums\WebhookDeliveryStatus;
use App\Jobs\DeliverCustomerWebhook;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Models\WebhookEvent;

final class FanOutWebhookEvent
{
    public function __invoke(WebhookEvent $event): void
    {
        if ($event->expires_at->isPast() || ! $event->type->isCustomerFacing()) {
            return;
        }

        $endpoints = WebhookEndpoint::query()
            ->where('workspace_id', $event->workspace_id)
            ->where('event_type', $event->type)
            ->where('enabled', true)
            ->orderBy('id')
            ->get();

        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::query()->firstOrCreate(
                [
                    'webhook_event_id' => $event->id,
                    'webhook_endpoint_id' => $endpoint->id,
                ],
                [
                    'workspace_id' => $event->workspace_id,
                    'attempt' => 0,
                    'status' => WebhookDeliveryStatus::Pending,
                ],
            );

            if ($delivery->wasRecentlyCreated) {
                DeliverCustomerWebhook::dispatch($delivery->id, 1);
            }
        }
    }
}
