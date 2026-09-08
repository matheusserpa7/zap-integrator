<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditAction: string
{
    case AllowlistAdded = 'allowlist.added';
    case AllowlistRemoved = 'allowlist.removed';
    case UserDisabled = 'user.disabled';
    case UserDeleted = 'user.deleted';
    case InstanceCreated = 'instance.created';
    case InstanceDisconnected = 'instance.disconnected';
    case InstanceDeleted = 'instance.deleted';
    case ApiTokenCreated = 'api_token.created';
    case ApiTokenRevoked = 'api_token.revoked';
    case WebhookEndpointCreated = 'webhook_endpoint.created';
    case WebhookSecretRotated = 'webhook_secret.rotated';
    case WebhookMappingChanged = 'webhook_mapping.changed';
}
