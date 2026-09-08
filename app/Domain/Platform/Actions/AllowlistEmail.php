<?php

declare(strict_types=1);

namespace App\Domain\Platform\Actions;

use App\Enums\AuditAction;
use App\Models\EmailAllowlist;
use App\Models\User;
use App\Support\NormalizedEmail;

final class AllowlistEmail
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    public function __invoke(string $email, User $actor): EmailAllowlist
    {
        $entry = EmailAllowlist::query()->create([
            'email' => NormalizedEmail::make($email),
            'created_by_user_id' => $actor->id,
        ]);

        ($this->recordAuditEvent)(
            action: AuditAction::AllowlistAdded,
            targetType: 'email_allowlist',
            targetId: $entry->id,
            actor: $actor,
            metadata: ['email' => $entry->email],
        );

        return $entry;
    }
}
