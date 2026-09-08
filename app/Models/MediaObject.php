<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageType;
use App\Support\HasPublicId;
use Database\Factories\MediaObjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property MessageType $type
 * @property Carbon $expires_at
 * @property-read Workspace $workspace
 * @property-read Instance $instance
 * @property-read Message $message
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'instance_id',
    'message_id',
    'type',
    'mime_type',
    'filename',
    'size_bytes',
    'checksum',
    'disk_path',
    'expires_at',
])]
class MediaObject extends Model
{
    /** @use HasFactory<MediaObjectFactory> */
    use HasFactory, HasPublicId;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'size_bytes' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'med_';
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
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicMetadata(): array
    {
        return [
            'id' => $this->public_id,
            'mime_type' => $this->mime_type,
            'filename' => $this->filename,
            'size_bytes' => $this->size_bytes,
            'checksum_sha256' => $this->checksum,
            'expires_at' => $this->expires_at->toIso8601String(),
            'download' => [
                'url' => '/api/v1/media/'.$this->public_id,
                'method' => 'GET',
                'auth' => 'bearer',
            ],
        ];
    }
}
