<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\HasPublicId;
use Database\Factories\PersonalAccessTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * @property list<string>|null $abilities
 * @property-read Workspace $workspace
 */
#[Fillable([
    'public_id',
    'workspace_id',
    'name',
    'token',
    'token_prefix',
    'abilities',
    'expires_at',
])]
#[Hidden(['token'])]
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /** @use HasFactory<PersonalAccessTokenFactory> */
    use HasFactory, HasPublicId;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'workspace_id',
        'name',
        'token',
        'token_prefix',
        'abilities',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function publicIdPrefix(): string
    {
        return 'tok_';
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public static function findToken($token): ?static
    {
        if (! is_string($token) || $token === '' || str_contains($token, '|')) {
            return null;
        }

        $prefix = (string) config('zap.api.token_prefix');

        if ($prefix === '' || ! str_starts_with($token, $prefix)) {
            return null;
        }

        /** @var static|null $accessToken */
        $accessToken = static::query()->where('token', hash('sha256', $token))->first();

        return $accessToken;
    }

    /**
     * @return array<string, mixed>
     */
    public function toInertia(): array
    {
        $abilities = $this->abilities ?? [];

        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'token_prefix' => $this->token_prefix,
            'abilities' => $abilities,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
