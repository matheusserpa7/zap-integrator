<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Instance;
use App\Models\User;
use App\Models\Workspace;

class InstancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasWorkspace();
    }

    public function view(User $user, Instance $instance): bool
    {
        return $user->isMemberOf($instance->workspace);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->isOwnerOf($workspace);
    }

    public function refreshQr(User $user, Instance $instance): bool
    {
        return $user->isMemberOf($instance->workspace);
    }

    public function disconnect(User $user, Instance $instance): bool
    {
        return $user->isOwnerOf($instance->workspace);
    }

    public function delete(User $user, Instance $instance): bool
    {
        return $user->isOwnerOf($instance->workspace);
    }
}
