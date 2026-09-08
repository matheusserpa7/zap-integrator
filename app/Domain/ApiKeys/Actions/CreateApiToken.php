<?php

declare(strict_types=1);

namespace App\Domain\ApiKeys\Actions;

use App\Domain\ApiKeys\Data\CreatedApiToken;
use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Enums\AuditAction;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Workspace;
use App\Support\PublicId;
use DateTimeInterface;
use Illuminate\Support\Str;

final class CreateApiToken
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    /**
     * @param  list<string>  $abilities
     */
    public function __invoke(
        User $actor,
        Workspace $workspace,
        string $name,
        array $abilities,
        ?DateTimeInterface $expiresAt = null,
    ): CreatedApiToken {
        $plainTextToken = $actor->generateTokenString();

        /** @var PersonalAccessToken $token */
        $token = $actor->tokens()->create([
            'public_id' => PublicId::make('tok_'),
            'workspace_id' => $workspace->id,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'token_prefix' => Str::substr($plainTextToken, 0, 13),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        ($this->recordAuditEvent)(
            action: AuditAction::ApiTokenCreated,
            targetType: 'api_token',
            targetId: $token->id,
            targetPublicId: $token->public_id,
            actor: $actor,
            workspace: $workspace,
            metadata: [
                'name' => $token->name,
                'abilities' => $abilities,
            ],
        );

        return new CreatedApiToken($token, $plainTextToken);
    }
}
