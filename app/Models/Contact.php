<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasPublicId;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Workspace $workspace
 * @property-read Instance $instance
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'instance_id',
    'wa_id',
    'display_name',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, HasPublicId;

    protected static function publicIdPrefix(): string
    {
        return 'ctc_';
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
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return HasOne<Conversation, $this>
     */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function displayLabel(): string
    {
        $name = trim((string) $this->display_name);

        return $name !== '' ? $name : $this->wa_id;
    }

    public function initials(): string
    {
        $label = $this->displayLabel();
        $parts = preg_split('/\s+/', $label) ?: [];
        $first = mb_substr($parts[0] ?? 'C', 0, 1);
        $last = count($parts) > 1 ? mb_substr((string) array_pop($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        return [
            'public_id' => $this->public_id,
            'display_name' => $this->displayLabel(),
            'wa_id' => $this->wa_id,
            'initials' => $this->initials(),
        ];
    }
}
