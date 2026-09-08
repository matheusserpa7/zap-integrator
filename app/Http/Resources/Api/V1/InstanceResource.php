<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Instance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Instance
 */
final class InstanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'status' => $this->status->value,
            'phone_number' => $this->phone_number,
            'connected_at' => $this->connected_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
        ];
    }
}
