<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_platform_admin;
    }

    public function disable(User $actor, User $user): bool
    {
        return $actor->is_platform_admin
            && $actor->isNot($user)
            && $user->disabled_at === null;
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->is_platform_admin && $actor->isNot($user);
    }
}
