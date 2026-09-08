<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use App\Support\HasPublicId;
use Database\Factories\WebhookDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property WebhookDeliveryStatus $status
 * @property Carbon|null $next_retry_at
 * @property Carbon|null $delivered_at
 * @property-read Workspace $workspace
 * @property-read WebhookEvent $event
 * @property-read WebhookEndpoint $endpoint
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'webhook_event_id',
    'webhook_endpoint_id',
    'attempt',
    'status',
    'http_status',
    'response_excerpt',
    'duration_ms',
    'next_retry_at',
    'delivered_at',
])]
class WebhookDelivery extends Model
{
    /** @use HasFactory<WebhookDeliveryFactory> */
    use HasFactory, HasPublicId;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WebhookDeliveryStatus::class,
            'attempt' => 'integer',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'dlv_';
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<WebhookEvent, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(WebhookEvent::class, 'webhook_event_id');
    }

    /**
     * @return BelongsTo<WebhookEndpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        $this->loadMissing(['event', 'endpoint']);

        return [
            'public_id' => $this->public_id,
            'event_id' => $this->event->public_id,
            'endpoint_id' => $this->endpoint->public_id,
            'attempt' => $this->attempt,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'http_status' => $this->http_status,
            'response_excerpt' => $this->response_excerpt,
            'duration_ms' => $this->duration_ms,
            'next_retry_at' => $this->next_retry_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
