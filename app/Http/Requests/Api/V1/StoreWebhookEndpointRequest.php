<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Enums\WebhookMappingValueMode;
use App\Enums\WebhookPayloadMode;
use App\Enums\ZapEventType;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = CurrentWorkspace::from($this);
        $endpoint = $this->route('endpoint');

        if (! $user instanceof User || ! $workspace instanceof Workspace) {
            return false;
        }

        if ($endpoint instanceof WebhookEndpoint) {
            return $user->can('update', $endpoint);
        }

        return $user->can('create', [WebhookEndpoint::class, $workspace]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:2048'],
            'description' => ['nullable', 'string', 'max:255'],
            'event_type' => ['required', 'string', Rule::in(array_map(
                fn (ZapEventType $type): string => $type->value,
                ZapEventType::customerFacing(),
            ))],
            'payload_mode' => ['required', 'string', Rule::enum(WebhookPayloadMode::class)],
            'enabled' => ['sometimes', 'boolean'],
            'body_mapping' => ['nullable', 'array'],
            'body_mapping.*.path' => ['required_with:body_mapping', 'string', 'max:120'],
            'body_mapping.*.value_mode' => ['required_with:body_mapping', Rule::enum(WebhookMappingValueMode::class)],
            'body_mapping.*.value' => ['present', 'string', 'max:2000'],
            'headers' => ['nullable', 'array'],
            'headers.*.name' => ['required_with:headers', 'string', 'max:80'],
            'headers.*.value_mode' => ['required_with:headers', Rule::enum(WebhookMappingValueMode::class)],
            'headers.*.value' => ['present', 'string', 'max:2000'],
            'rotate_secret' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function endpointInput(): array
    {
        return [
            ...$this->safe()->except(['rotate_secret']),
            'enabled' => $this->boolean('enabled', true),
            'body_mapping' => $this->input('body_mapping', []),
            'headers' => $this->input('headers', []),
        ];
    }
}
