<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ZapEventType;
use App\Support\HasPublicId;
use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property ZapEventType $type
 * @property array<string, mixed> $payload
 * @property Carbon $occurred_at
 * @property Carbon $received_at
 * @property Carbon $expires_at
 * @property-read Workspace $workspace
 * @property-read Instance|null $instance
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'instance_id',
    'type',
    'provider_event_type',
    'provider_event_id',
    'payload',
    'payload_hash',
    'occurred_at',
    'received_at',
    'expires_at',
])]
class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory, HasPublicId;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ZapEventType::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'evt_';
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Instance, $this>
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    /**
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
}
