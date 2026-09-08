<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
final class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['conversation', 'media']);

        return [
            'id' => $this->public_id,
            'conversation_id' => $this->conversation->public_id,
            'instance_id' => $this->instance?->public_id,
            'direction' => $this->direction->value,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'body' => $this->body,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'media' => $this->media?->toPublicMetadata(),
        ];
    }
}
