<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Domain\Webhooks\Exceptions\WebhookMappingInvalid;
use App\Enums\WebhookPayloadMode;
use App\Enums\ZapEventType;

final class NormalizeWebhookEndpointInput
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{
     *     name: string,
     *     url: string,
     *     description: ?string,
     *     event_type: ZapEventType,
     *     payload_mode: WebhookPayloadMode,
     *     body_mapping: array<int, array{path: string, value_mode: string, value: string}>,
     *     headers: array<int, array{name: string, value_mode: string, value: string}>,
     *     enabled: bool
     * }
     */
    public function __invoke(
        array $input,
        ValidateWebhookDestination $validateDestination,
        ValidateWebhookMapping $validateMapping,
        ValidateWebhookHeaders $validateHeaders,
    ): array {
        $eventType = ZapEventType::tryFrom((string) ($input['event_type'] ?? ''));

        if (! $eventType instanceof ZapEventType || ! $eventType->isCustomerFacing()) {
            throw new WebhookMappingInvalid('The event type is invalid.');
        }

        $mode = WebhookPayloadMode::tryFrom((string) ($input['payload_mode'] ?? WebhookPayloadMode::Canonical->value))
            ?? WebhookPayloadMode::Canonical;

        $mapping = $this->mappingRows($input['body_mapping'] ?? []);
        $headers = $this->headerRows($input['headers'] ?? []);

        if ($mode === WebhookPayloadMode::Canonical) {
            $mapping = [];
        } else {
            $validateMapping($eventType, $mapping);
        }

        $validateDestination((string) ($input['url'] ?? ''));
        $validateHeaders($headers);

        return [
            'name' => trim((string) ($input['name'] ?? '')),
            'url' => trim((string) ($input['url'] ?? '')),
            'description' => $this->nullableString($input['description'] ?? null),
            'event_type' => $eventType,
            'payload_mode' => $mode,
            'body_mapping' => $mapping,
            'headers' => $headers,
            'enabled' => (bool) ($input['enabled'] ?? true),
        ];
    }

    /**
     * @return array<int, array{path: string, value_mode: string, value: string}>
     */
    private function mappingRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized[] = [
                'path' => (string) ($row['path'] ?? ''),
                'value_mode' => (string) ($row['value_mode'] ?? ''),
                'value' => (string) ($row['value'] ?? ''),
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{name: string, value_mode: string, value: string}>
     */
    private function headerRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized[] = [
                'name' => (string) ($row['name'] ?? ''),
                'value_mode' => (string) ($row['value_mode'] ?? 'fixed'),
                'value' => (string) ($row['value'] ?? ''),
            ];
        }

        return $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
