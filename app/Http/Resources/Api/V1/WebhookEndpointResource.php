<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WebhookEndpoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookEndpoint
 */
final class WebhookEndpointResource extends JsonResource
{
    public ?string $plainTextSecret = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'url' => $this->url,
            'description' => $this->description,
            'event_type' => $this->event_type->value,
            'payload_mode' => $this->payload_mode->value,
            'body_mapping' => $this->mappingRows(),
            'headers' => $this->headerRows(),
            'enabled' => $this->enabled,
            'failure_count' => $this->failure_count,
            'secret' => $this->plainTextSecret,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
