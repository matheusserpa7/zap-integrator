<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EmailAllowlist;
use App\Models\User;

class EmailAllowlistPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_platform_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_platform_admin;
    }

    public function delete(User $user, EmailAllowlist $emailAllowlist): bool
    {
        return $user->is_platform_admin;
    }
}
