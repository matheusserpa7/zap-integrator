<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasWorkspace();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->isMemberOf($conversation->workspace);
    }
}
