<?php

declare(strict_types=1);

namespace App\Domain\Webhooks\Actions;

use App\Domain\Platform\Actions\RecordAuditEvent;
use App\Domain\Webhooks\GenerateWebhookSecret;
use App\Domain\Webhooks\NormalizeWebhookEndpointInput;
use App\Domain\Webhooks\ValidateWebhookDestination;
use App\Domain\Webhooks\ValidateWebhookHeaders;
use App\Domain\Webhooks\ValidateWebhookMapping;
use App\Enums\AuditAction;
use App\Enums\WebhookPayloadMode;
use App\Models\User;
use App\Models\WebhookEndpoint;

final class UpdateWebhookEndpoint
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
    public function __invoke(WebhookEndpoint $endpoint, User $actor, array $input, bool $rotateSecret = false): WebhookEndpoint
    {
        $data = ($this->normalize)(
            $input,
            $this->validateDestination,
            $this->validateMapping,
            $this->validateHeaders,
        );

        $mappingChanged = $endpoint->payload_mode !== $data['payload_mode']
            || $endpoint->body_mapping !== $data['body_mapping'];

        $endpoint->fill([
            'name' => $data['name'],
            'url' => $data['url'],
            'description' => $data['description'],
            'event_type' => $data['event_type'],
            'payload_mode' => $data['payload_mode'],
            'body_mapping' => $data['payload_mode'] === WebhookPayloadMode::Canonical ? [] : $data['body_mapping'],
            'headers_mapping_encrypted' => $data['headers'],
            'enabled' => $data['enabled'],
        ]);

        if ($rotateSecret) {
            $endpoint->secret_encrypted = ($this->generateSecret)();
        }

        $endpoint->save();

        if ($rotateSecret) {
            ($this->recordAuditEvent)(
                action: AuditAction::WebhookSecretRotated,
                targetType: 'webhook_endpoint',
                targetId: $endpoint->id,
                targetPublicId: $endpoint->public_id,
                actor: $actor,
                workspace: $endpoint->workspace,
            );
        }

        if ($mappingChanged) {
            ($this->recordAuditEvent)(
                action: AuditAction::WebhookMappingChanged,
                targetType: 'webhook_endpoint',
                targetId: $endpoint->id,
                targetPublicId: $endpoint->public_id,
                actor: $actor,
                workspace: $endpoint->workspace,
                metadata: ['payload_mode' => $endpoint->payload_mode->value],
            );
        }

        return $endpoint;
    }
}
