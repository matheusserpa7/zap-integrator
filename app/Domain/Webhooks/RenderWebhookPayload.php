<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Enums\WebhookMappingValueMode;
use App\Enums\WebhookPayloadMode;
use App\Enums\ZapEventType;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Arr;

final class RenderWebhookPayload
{
    public function __construct(
        private WebhookEventCatalog $catalog,
        private ValidateWebhookMapping $validateMapping,
    ) {}

    /**
     * @param  array<string, mixed>  $canonical
     * @return array<string, mixed>
     */
    public function body(WebhookEndpoint $endpoint, array $canonical): array
    {
        if ($endpoint->payload_mode === WebhookPayloadMode::Canonical) {
            return $canonical;
        }

        return $this->project($endpoint->event_type, $endpoint->mappingRows(), $canonical);
    }

    /**
     * @param  array<int, array{path: string, value_mode: string, value: string}>  $rows
     * @param  array<string, mixed>  $canonical
     * @return array<string, mixed>
     */
    public function project(ZapEventType $type, array $rows, array $canonical): array
    {
        ($this->validateMapping)($type, $rows);

        $output = [];

        foreach ($rows as $row) {
            $mode = WebhookMappingValueMode::from($row['value_mode']);
            Arr::set($output, $row['path'], $this->resolve($type, $mode, $row['value'], $canonical));
        }

        return $output;
    }

    /**
     * @param  array<string, mixed>  $canonical
     * @return array<string, string>
     */
    public function headers(WebhookEndpoint $endpoint, array $canonical): array
    {
        $headers = [];

        foreach ($endpoint->headerRows() as $row) {
            $mode = WebhookMappingValueMode::tryFrom($row['value_mode']) ?? WebhookMappingValueMode::Fixed;
            $resolved = $this->resolve($endpoint->event_type, $mode, $row['value'], $canonical);
            $headers[$row['name']] = is_scalar($resolved) || $resolved === null ? (string) ($resolved ?? '') : '';
        }

        return $headers;
    }

    /**
     * @param  array<string, mixed>  $canonical
     */
    public function resolve(ZapEventType $type, WebhookMappingValueMode $mode, string $value, array $canonical): mixed
    {
        return match ($mode) {
            WebhookMappingValueMode::Fixed => $value,
            WebhookMappingValueMode::Field => $this->field($type, $value, $canonical),
            WebhookMappingValueMode::Expression => $this->expression($type, $value, $canonical),
        };
    }

    /**
     * @param  array<string, mixed>  $canonical
     */
    private function field(ZapEventType $type, string $path, array $canonical): mixed
    {
        $canonicalPath = $this->catalog->canonicalPath($type, $path);

        if ($canonicalPath === null) {
            return null;
        }

        return Arr::get($canonical, $canonicalPath);
    }

    /**
     * @param  array<string, mixed>  $canonical
     */
    private function expression(ZapEventType $type, string $template, array $canonical): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([A-Za-z][A-Za-z0-9_.]*)\s*\}\}/',
            function (array $matches) use ($type, $canonical): string {
                $resolved = $this->field($type, $matches[1], $canonical);

                if ($resolved === null) {
                    return '';
                }

                return is_scalar($resolved) ? (string) $resolved : '';
            },
            $template,
        );
    }
}
