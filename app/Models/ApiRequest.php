<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApiRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property array<string, mixed>|null $response_body
 * @property Carbon $expires_at
 * @property-read Workspace $workspace
 * @property-read PersonalAccessToken $token
 */
#[Fillable([
    'workspace_id',
    'api_token_id',
    'idempotency_key',
    'request_hash',
    'response_status',
    'response_body',
    'expires_at',
])]
class ApiRequest extends Model
{
    /** @use HasFactory<ApiRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'response_body' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<PersonalAccessToken, $this>
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'api_token_id');
    }

    public function isComplete(): bool
    {
        return $this->response_status !== null && $this->response_body !== null;
    }
}
