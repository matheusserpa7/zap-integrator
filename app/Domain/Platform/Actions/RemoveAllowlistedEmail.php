<?php

declare(strict_types=1);

namespace App\Domain\Platform\Actions;

use App\Enums\AuditAction;
use App\Models\EmailAllowlist;
use App\Models\User;

final class RemoveAllowlistedEmail
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    public function __invoke(EmailAllowlist $entry, User $actor): void
    {
        ($this->recordAuditEvent)(
            action: AuditAction::AllowlistRemoved,
            targetType: 'email_allowlist',
            targetId: $entry->id,
            actor: $actor,
            metadata: ['email' => $entry->email],
        );

        $entry->delete();
    }
}
