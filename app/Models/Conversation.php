<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasPublicId;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $last_message_at
 * @property-read Workspace $workspace
 * @property-read Instance $instance
 * @property-read Contact $contact
 * @property-read Message|null $latestMessage
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'instance_id',
    'contact_id',
    'last_message_at',
])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory, HasPublicId;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'cnv_';
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
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany(['occurred_at', 'id']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        $this->loadMissing(['contact', 'latestMessage']);

        $latest = $this->latestMessage;

        return [
            'public_id' => $this->public_id,
            'contact' => $this->contact->toInertia(),
            'preview' => $latest?->body,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
        ];
    }
}
