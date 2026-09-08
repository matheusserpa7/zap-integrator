<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MediaObject;
use App\Models\User;

class MediaObjectPolicy
{
    public function view(User $user, MediaObject $mediaObject): bool
    {
        return $user->isMemberOf($mediaObject->workspace);
    }
}
