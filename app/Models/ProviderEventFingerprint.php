<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ZapEventType;
use Database\Factories\ProviderEventFingerprintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ZapEventType $event_type
 * @property array<string, mixed>|null $payload
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 * @property Carbon $expires_at
 * @property-read Instance $instance
 * @property-read Workspace $workspace
 */
#[Fillable([
    'workspace_id',
    'instance_id',
    'fingerprint',
    'provider_event_type',
    'provider_event_id',
    'event_type',
    'payload',
    'received_at',
    'processed_at',
    'expires_at',
])]
class ProviderEventFingerprint extends Model
{
    /** @use HasFactory<ProviderEventFingerprintFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => ZapEventType::class,
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Instance, $this>
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
