<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Domain\Webhooks\Data\CreatedWebhookEndpoint;
use App\Domain\Webhooks\GenerateWebhookSecret;
use App\Enums\AuditAction;
use App\Models\User;
use App\Models\WebhookEndpoint;

final class DuplicateWebhookEndpoint
{
    public function __construct(
        private GenerateWebhookSecret $generateSecret,
        private RecordAuditEvent $recordAuditEvent,
    ) {}

    public function __invoke(WebhookEndpoint $endpoint, User $actor): CreatedWebhookEndpoint
    {
        $secret = ($this->generateSecret)();

        $copy = WebhookEndpoint::query()->create([
            'workspace_id' => $endpoint->workspace_id,
            'name' => mb_strimwidth($endpoint->name.' (cópia)', 0, 80, ''),
            'url' => $endpoint->url,
            'description' => $endpoint->description,
            'event_type' => $endpoint->event_type,
            'payload_mode' => $endpoint->payload_mode,
            'body_mapping' => $endpoint->mappingRows(),
            'headers_mapping_encrypted' => $endpoint->headerRows(),
            'secret_encrypted' => $secret,
            'enabled' => $endpoint->enabled,
        ]);

        ($this->recordAuditEvent)(
            action: AuditAction::WebhookEndpointCreated,
            targetType: 'webhook_endpoint',
            targetId: $copy->id,
            targetPublicId: $copy->public_id,
            actor: $actor,
            workspace: $endpoint->workspace,
            metadata: [
                'duplicated_from' => $endpoint->public_id,
                'event_type' => $copy->event_type->value,
            ],
        );

        return new CreatedWebhookEndpoint($copy, $secret);
    }
}
