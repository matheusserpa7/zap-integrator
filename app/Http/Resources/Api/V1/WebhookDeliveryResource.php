<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WebhookDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookDelivery
 */
final class WebhookDeliveryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['event', 'endpoint']);

        return [
            'id' => $this->public_id,
            'event_id' => $this->event->public_id,
            'endpoint_id' => $this->endpoint->public_id,
            'attempt' => $this->attempt,
            'status' => $this->status->value,
            'http_status' => $this->http_status,
            'response_excerpt' => $this->response_excerpt,
            'duration_ms' => $this->duration_ms,
            'next_retry_at' => $this->next_retry_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
