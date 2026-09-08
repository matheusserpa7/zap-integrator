<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Enums\MessageType;
use App\Enums\ZapEventType;
use App\Models\MediaObject;
use App\Models\Message;

final class BuildCanonicalMessagePayload
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(ZapEventType $type, Message $message, ?MediaObject $media = null, ?string $caption = null): array
    {
        $message->loadMissing(['instance', 'conversation.contact', 'media']);
        $media ??= $message->media;
        $contact = $message->conversation->contact;

        $data = [
            'instance_id' => $message->instance->public_id,
            'message_id' => $message->public_id,
            'conversation_id' => $message->conversation->public_id,
            'from' => $contact->wa_id,
            'type' => $message->type->value,
            'contact_name' => $contact->displayLabel(),
        ];

        if ($message->type === MessageType::Text) {
            $data['text'] = $message->body;
        } elseif ($caption !== null && $caption !== '') {
            $data['caption'] = $caption;
        }

        if ($media instanceof MediaObject) {
            $data['media'] = $media->toPublicMetadata();
        }

        return [
            'id' => '',
            'type' => $type->value,
            'created_at' => now()->toIso8601String(),
            'data' => $data,
        ];
    }
}
