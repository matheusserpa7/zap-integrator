<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasWorkspace();
    }

    public function view(User $user, Message $message): bool
    {
        return $user->isMemberOf($message->workspace);
    }
}
