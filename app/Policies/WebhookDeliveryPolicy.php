<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookDelivery;

class WebhookDeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasWorkspace();
    }

    public function view(User $user, WebhookDelivery $webhookDelivery): bool
    {
        return $user->isMemberOf($webhookDelivery->workspace);
    }

    public function retry(User $user, WebhookDelivery $webhookDelivery): bool
    {
        return $user->isOwnerOf($webhookDelivery->workspace);
    }
}
