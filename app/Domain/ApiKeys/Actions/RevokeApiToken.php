<?php

declare(strict_types=1);

namespace App\Domain\ApiKeys\Actions;

use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Enums\AuditAction;
use App\Models\PersonalAccessToken;
use App\Models\User;

final class RevokeApiToken
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    public function __invoke(PersonalAccessToken $token, User $actor): void
    {
        $workspace = $token->workspace;
        $publicId = $token->public_id;
        $tokenId = $token->id;
        $name = $token->name;

        $token->delete();

        ($this->recordAuditEvent)(
            action: AuditAction::ApiTokenRevoked,
            targetType: 'api_token',
            targetId: $tokenId,
            targetPublicId: $publicId,
            actor: $actor,
            workspace: $workspace,
            metadata: [
                'name' => $name,
            ],
        );
    }
}
