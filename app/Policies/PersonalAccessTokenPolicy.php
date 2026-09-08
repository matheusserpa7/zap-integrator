<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\Workspace;

class PersonalAccessTokenPolicy
{
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->isOwnerOf($workspace);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->isOwnerOf($workspace);
    }

    public function delete(User $user, PersonalAccessToken $token): bool
    {
        return $user->isOwnerOf($token->workspace);
    }
}
