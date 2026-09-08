<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
final class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['contact', 'latestMessage', 'instance']);

        return [
            'id' => $this->public_id,
            'instance_id' => $this->instance->public_id,
            'contact' => [
                'id' => $this->contact->public_id,
                'wa_id' => $this->contact->wa_id,
                'display_name' => $this->contact->displayLabel(),
            ],
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'last_message' => $this->latestMessage === null ? null : [
                'id' => $this->latestMessage->public_id,
                'body' => $this->latestMessage->body,
                'direction' => $this->latestMessage->direction->value,
                'type' => $this->latestMessage->type->value,
                'status' => $this->latestMessage->status->value,
            ],
        ];
    }
}
