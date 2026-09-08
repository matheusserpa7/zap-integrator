<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\User;
use App\Models\Workspace;

final class WorkspaceChannel
{
    public function join(User $user, string $workspacePublicId): bool
    {
        $workspace = Workspace::query()->where('public_id', $workspacePublicId)->first();

        return $workspace instanceof Workspace && $user->isMemberOf($workspace);
    }
}
