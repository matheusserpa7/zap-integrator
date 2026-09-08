<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\Workspace;

class WebhookEndpointPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasWorkspace();
    }

    public function view(User $user, WebhookEndpoint $webhookEndpoint): bool
    {
        return $user->isMemberOf($webhookEndpoint->workspace);
    }

    public function create(User $user, Workspace $workspace): bool
    {
        return $user->isOwnerOf($workspace);
    }

    public function update(User $user, WebhookEndpoint $webhookEndpoint): bool
    {
        return $user->isOwnerOf($webhookEndpoint->workspace);
    }

    public function delete(User $user, WebhookEndpoint $webhookEndpoint): bool
    {
        return $user->isOwnerOf($webhookEndpoint->workspace);
    }

    public function test(User $user, WebhookEndpoint $webhookEndpoint): bool
    {
        return $user->isOwnerOf($webhookEndpoint->workspace);
    }

    public function duplicate(User $user, WebhookEndpoint $webhookEndpoint): bool
    {
        return $user->isOwnerOf($webhookEndpoint->workspace);
    }
}
