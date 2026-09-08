<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Support\HasPublicId;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property MessageDirection $direction
 * @property MessageType $type
 * @property MessageStatus $status
 * @property Carbon $occurred_at
 * @property-read Workspace $workspace
 * @property-read Instance $instance
 * @property-read Conversation $conversation
 * @property-read MediaObject|null $media
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'instance_id',
    'conversation_id',
    'provider_message_id',
    'direction',
    'type',
    'status',
    'body',
    'media_id',
    'occurred_at',
])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, HasPublicId;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'type' => MessageType::class,
            'status' => MessageStatus::class,
            'occurred_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'msg_';
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
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<MediaObject, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaObject::class, 'media_id');
    }

    public function mediaExpired(): bool
    {
        return $this->type->hasMedia() && ($this->media === null || $this->media->isExpired());
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        $this->loadMissing('media');

        return [
            'public_id' => $this->public_id,
            'direction' => $this->direction->value,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'body' => $this->body,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'media_expired' => $this->mediaExpired(),
        ];
    }
}
