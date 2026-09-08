<?php

declare(strict_types=1);

namespace App\Domain\Platform\Actions;

use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DisableUser
{
    public function __construct(private RecordAuditEvent $recordAuditEvent) {}

    public function __invoke(User $actor, User $user): void
    {
        DB::transaction(function () use ($actor, $user): void {
            $user->forceFill([
                'disabled_at' => now(),
            ])->save();

            ($this->recordAuditEvent)(
                action: AuditAction::UserDisabled,
                targetType: 'user',
                targetId: $user->id,
                targetPublicId: $user->public_id,
                actor: $actor,
                metadata: ['email' => $user->email],
            );

            $this->forgetSessions($user);
        });
    }

    private function forgetSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
