<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Domain\Webhooks\Data\CreatedWebhookEndpoint;
use App\Domain\Webhooks\GenerateWebhookSecret;
use App\Domain\Webhooks\NormalizeWebhookEndpointInput;
use App\Domain\Webhooks\ValidateWebhookDestination;
use App\Domain\Webhooks\ValidateWebhookHeaders;
use App\Domain\Webhooks\ValidateWebhookMapping;
use App\Enums\AuditAction;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\Workspace;

final class CreateWebhookEndpoint
{
    public function __construct(
        private NormalizeWebhookEndpointInput $normalize,
        private ValidateWebhookDestination $validateDestination,
        private ValidateWebhookMapping $validateMapping,
        private ValidateWebhookHeaders $validateHeaders,
        private GenerateWebhookSecret $generateSecret,
        private RecordAuditEvent $recordAuditEvent,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(Workspace $workspace, User $actor, array $input): CreatedWebhookEndpoint
    {
        $data = ($this->normalize)(
            $input,
            $this->validateDestination,
            $this->validateMapping,
            $this->validateHeaders,
        );

        $secret = ($this->generateSecret)();

        $endpoint = WebhookEndpoint::query()->create([
            'workspace_id' => $workspace->id,
            'name' => $data['name'],
            'url' => $data['url'],
            'description' => $data['description'],
            'event_type' => $data['event_type'],
            'payload_mode' => $data['payload_mode'],
            'body_mapping' => $data['body_mapping'],
            'headers_mapping_encrypted' => $data['headers'],
            'secret_encrypted' => $secret,
            'enabled' => $data['enabled'],
        ]);

        ($this->recordAuditEvent)(
            action: AuditAction::WebhookEndpointCreated,
            targetType: 'webhook_endpoint',
            targetId: $endpoint->id,
            targetPublicId: $endpoint->public_id,
            actor: $actor,
            workspace: $workspace,
            metadata: [
                'event_type' => $endpoint->event_type->value,
                'payload_mode' => $endpoint->payload_mode->value,
            ],
        );

        return new CreatedWebhookEndpoint($endpoint, $secret);
    }
}
