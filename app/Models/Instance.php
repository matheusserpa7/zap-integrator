<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InstanceProvider;
use App\Enums\InstanceStatus;
use App\Support\HasPublicId;
use Database\Factories\InstanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property InstanceStatus $status
 * @property InstanceProvider $provider
 * @property string|null $provider_instance_token_encrypted
 * @property string|null $provider_webhook_secret_encrypted
 * @property string|null $qr_code_encrypted
 * @property Carbon|null $qr_expires_at
 * @property Carbon|null $connected_at
 * @property Carbon|null $last_seen_at
 * @property-read Workspace $workspace
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'name',
    'provider',
    'provider_instance_name',
    'provider_instance_token_encrypted',
    'provider_webhook_secret_encrypted',
    'status',
    'phone_number',
    'qr_code_encrypted',
    'qr_expires_at',
    'connected_at',
    'last_seen_at',
    'last_error',
])]
#[Hidden([
    'provider_instance_token_encrypted',
    'provider_webhook_secret_encrypted',
    'qr_code_encrypted',
])]
class Instance extends Model
{
    /** @use HasFactory<InstanceFactory> */
    use HasFactory, HasPublicId, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => InstanceProvider::class,
            'status' => InstanceStatus::class,
            'provider_instance_token_encrypted' => 'encrypted',
            'provider_webhook_secret_encrypted' => 'encrypted',
            'qr_code_encrypted' => 'encrypted',
            'qr_expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'ins_';
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return HasMany<ProviderEventFingerprint, $this>
     */
    public function providerEventFingerprints(): HasMany
    {
        return $this->hasMany(ProviderEventFingerprint::class);
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function qrCodeForClient(): ?string
    {
        if ($this->status !== InstanceStatus::WaitingQr) {
            return null;
        }

        if ($this->qr_expires_at === null || $this->qr_expires_at->isPast()) {
            return null;
        }

        $qr = $this->qr_code_encrypted;

        return is_string($qr) && $qr !== '' ? $qr : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(bool $includeQr = false): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'status' => $this->status->value,
            'phone_number' => $this->phone_number,
            'qr_code' => $includeQr ? $this->qrCodeForClient() : null,
            'qr_expires_at' => $includeQr ? $this->qr_expires_at?->toIso8601String() : null,
            'connected_at' => $this->connected_at?->toIso8601String(),
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'last_error' => $this->last_error,
        ];
    }
}
