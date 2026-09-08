<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookPayloadMode;
use App\Enums\ZapEventType;
use App\Support\HasPublicId;
use Database\Factories\WebhookEndpointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property WebhookPayloadMode $payload_mode
 * @property ZapEventType $event_type
 * @property array<int, array{path: string, value_mode: string, value: string}> $body_mapping
 * @property array<int, array{name: string, value_mode: string, value: string}> $headers_mapping_encrypted
 * @property string $secret_encrypted
 * @property-read Workspace $workspace
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'name',
    'url',
    'description',
    'event_type',
    'payload_mode',
    'body_mapping',
    'headers_mapping_encrypted',
    'secret_encrypted',
    'enabled',
    'failure_count',
])]
#[Hidden([
    'secret_encrypted',
    'headers_mapping_encrypted',
])]
class WebhookEndpoint extends Model
{
    /** @use HasFactory<WebhookEndpointFactory> */
    use HasFactory, HasPublicId;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => ZapEventType::class,
            'payload_mode' => WebhookPayloadMode::class,
            'body_mapping' => 'array',
            'headers_mapping_encrypted' => 'encrypted:array',
            'secret_encrypted' => 'encrypted',
            'enabled' => 'boolean',
            'failure_count' => 'integer',
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'payload_mode' => 'canonical',
        'enabled' => true,
        'failure_count' => 0,
    ];

    protected static function publicIdPrefix(): string
    {
        return 'wh_';
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * @return array<int, array{path: string, value_mode: string, value: string}>
     */
    public function mappingRows(): array
    {
        $rows = $this->body_mapping ?? [];

        return array_values(array_filter(
            $rows,
            fn (mixed $row): bool => is_array($row) && isset($row['path'], $row['value_mode'], $row['value']),
        ));
    }

    /**
     * @return array<int, array{name: string, value_mode: string, value: string}>
     */
    public function headerRows(): array
    {
        $rows = $this->headers_mapping_encrypted ?? [];

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(
            $rows,
            fn (mixed $row): bool => is_array($row) && isset($row['name'], $row['value_mode'], $row['value']),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'event_type' => $this->event_type->value,
            'payload_mode' => $this->payload_mode->value,
            'body_mapping' => $this->mappingRows(),
            'headers' => $this->headerRows(),
            'enabled' => $this->enabled,
            'failure_count' => $this->failure_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
