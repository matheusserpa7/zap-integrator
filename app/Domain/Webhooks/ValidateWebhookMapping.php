<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Domain\Webhooks\Exceptions\WebhookMappingInvalid;
use App\Enums\WebhookMappingValueMode;
use App\Enums\ZapEventType;

final class ValidateWebhookMapping
{
    public function __construct(private WebhookEventCatalog $catalog) {}

    /**
     * @param  array<int, array{path?: mixed, value_mode?: mixed, value?: mixed}>  $rows
     */
    public function __invoke(ZapEventType $type, array $rows): void
    {
        $seen = [];

        foreach ($rows as $index => $row) {
            $path = (string) ($row['path'] ?? '');
            $mode = WebhookMappingValueMode::tryFrom((string) ($row['value_mode'] ?? ''));
            $value = (string) ($row['value'] ?? '');

            if (! $this->isOutputPath($path)) {
                throw new WebhookMappingInvalid("Mapping row {$index} has an invalid output path.");
            }

            if (isset($seen[$path])) {
                throw new WebhookMappingInvalid("Mapping path [{$path}] is duplicated.");
            }

            $seen[$path] = true;

            if ($mode === null) {
                throw new WebhookMappingInvalid("Mapping row {$index} has an invalid value mode.");
            }

            match ($mode) {
                WebhookMappingValueMode::Field => $this->assertAllowedPath($type, $value),
                WebhookMappingValueMode::Fixed => $this->assertFixed($value),
                WebhookMappingValueMode::Expression => $this->assertExpression($type, $value),
            };
        }
    }

    /**
     * @return list<string>
     */
    public function expressionPaths(string $template): array
    {
        preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/', $template, $matches);

        return $matches[1];
    }

    private function isOutputPath(string $path): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z0-9_]*(?:\.[A-Za-z][A-Za-z0-9_]*)*$/', $path);
    }

    private function assertAllowedPath(ZapEventType $type, string $path): void
    {
        if (! $this->catalog->isAllowedPath($type, $path)) {
            throw new WebhookMappingInvalid("Path [{$path}] is not allowlisted for {$type->value}.");
        }
    }

    private function assertFixed(string $value): void
    {
        if (mb_strlen($value) > 2000) {
            throw new WebhookMappingInvalid('Fixed mapping values may not exceed 2000 characters.');
        }
    }

    private function assertExpression(ZapEventType $type, string $template): void
    {
        if ($template === '' || mb_strlen($template) > 2000) {
            throw new WebhookMappingInvalid('Expression mapping values must be between 1 and 2000 characters.');
        }

        if (preg_match('/\{\{[^}]*[|()#\/][^}]*\}\}/', $template) === 1) {
            throw new WebhookMappingInvalid('Expressions may not include filters, loops, or function calls.');
        }

        $placeholders = $this->expressionPaths($template);

        if ($placeholders === []) {
            throw new WebhookMappingInvalid('Expressions must include at least one {{ path }} substitution.');
        }

        foreach ($placeholders as $path) {
            if (preg_match('/^[A-Za-z][A-Za-z0-9_.]*$/', $path) !== 1) {
                throw new WebhookMappingInvalid("Expression path [{$path}] is invalid.");
            }

            $this->assertAllowedPath($type, $path);
        }
    }
}
