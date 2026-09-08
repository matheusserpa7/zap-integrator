<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Enums\ZapEventType;
use App\Models\Instance;
use App\Models\WebhookEvent;
use App\Support\PublicId;
use Illuminate\Support\Carbon;

final class PersistCanonicalWebhookEvent
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(
        Instance $instance,
        ZapEventType $type,
        array $payload,
        ?string $providerEventType = null,
        ?string $providerEventId = null,
        ?Carbon $occurredAt = null,
    ): WebhookEvent {
        $event = new WebhookEvent([
            'workspace_id' => $instance->workspace_id,
            'instance_id' => $instance->id,
            'type' => $type,
            'provider_event_type' => $providerEventType,
            'provider_event_id' => $providerEventId,
            'occurred_at' => $occurredAt ?? now(),
            'received_at' => now(),
            'expires_at' => now()->addDays(max(1, (int) config('zap.webhooks.event_retention_days', 7))),
        ]);
        $event->public_id = PublicId::make('evt_');

        $payload['id'] = $event->public_id;
        $encoded = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $event->payload = $payload;
        $event->payload_hash = hash('sha256', $encoded);
        $event->save();

        return $event;
    }
}
