<?php

declare(strict_types=1);

namespace App\Domain\Webhooks;

use App\Enums\ZapEventType;
use Illuminate\Support\Arr;

final class WebhookEventCatalog
{
    /**
     * @return list<string>
     */
    public function paths(ZapEventType $type): array
    {
        return array_keys($this->aliases($type));
    }

    /**
     * @return array<string, string>
     */
    public function aliases(ZapEventType $type): array
    {
        $shared = [
            'id' => 'id',
            'type' => 'type',
            'created_at' => 'created_at',
            'event.type' => 'type',
            'event.timestamp' => 'created_at',
            'data.instance_id' => 'data.instance_id',
            'instance.id' => 'data.instance_id',
        ];

        $message = [
            'data.message_id' => 'data.message_id',
            'data.conversation_id' => 'data.conversation_id',
            'data.from' => 'data.from',
            'data.type' => 'data.type',
            'data.text' => 'data.text',
            'data.caption' => 'data.caption',
            'data.contact_name' => 'data.contact_name',
            'data.media.id' => 'data.media.id',
            'data.media.mime_type' => 'data.media.mime_type',
            'data.media.filename' => 'data.media.filename',
            'data.media.size_bytes' => 'data.media.size_bytes',
            'data.media.checksum_sha256' => 'data.media.checksum_sha256',
            'data.media.expires_at' => 'data.media.expires_at',
            'data.media.download.url' => 'data.media.download.url',
            'data.media.download.method' => 'data.media.download.method',
            'data.media.download.auth' => 'data.media.download.auth',
            'message.id' => 'data.message_id',
            'message.text' => 'data.text',
            'message.from' => 'data.from',
            'contact.name' => 'data.contact_name',
            'media.id' => 'data.media.id',
        ];

        $instance = [
            'data.status' => 'data.status',
            'data.phone_number' => 'data.phone_number',
            'data.expires_at' => 'data.expires_at',
        ];

        return match ($type) {
            ZapEventType::MessageReceived, ZapEventType::MessageUpdated, ZapEventType::MessageSent, ZapEventType::MessageFailed => [
                ...$shared,
                ...$message,
            ],
            ZapEventType::InstanceQrUpdated, ZapEventType::InstanceConnected, ZapEventType::InstanceDisconnected => [
                ...$shared,
                ...$instance,
            ],
            default => $shared,
        };
    }

    public function isAllowedPath(ZapEventType $type, string $path): bool
    {
        return array_key_exists($path, $this->aliases($type));
    }

    public function canonicalPath(ZapEventType $type, string $path): ?string
    {
        return $this->aliases($type)[$path] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function fixture(ZapEventType $type): array
    {
        $createdAt = '2026-09-07T13:00:00+00:00';

        return match ($type) {
            ZapEventType::MessageReceived => [
                'id' => 'evt_019fixture000000000000000001',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [
                    'instance_id' => 'ins_019fixture000000000000000001',
                    'message_id' => 'msg_019fixture000000000000000001',
                    'conversation_id' => 'cnv_019fixture000000000000000001',
                    'from' => '5511999990101',
                    'type' => 'text',
                    'text' => 'Olá! Quero saber como integrar com meu CRM.',
                    'contact_name' => 'Ana Ribeiro',
                ],
            ],
            ZapEventType::MessageUpdated => [
                'id' => 'evt_019fixture000000000000000002',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [
                    'instance_id' => 'ins_019fixture000000000000000001',
                    'message_id' => 'msg_019fixture000000000000000001',
                    'conversation_id' => 'cnv_019fixture000000000000000001',
                    'from' => '5511999990101',
                    'type' => 'text',
                    'text' => 'Olá! Quero saber como integrar com meu CRM.',
                    'contact_name' => 'Ana Ribeiro',
                ],
            ],
            ZapEventType::MessageSent => [
                'id' => 'evt_019fixture000000000000000003',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [
                    'instance_id' => 'ins_019fixture000000000000000001',
                    'message_id' => 'msg_019fixture000000000000000002',
                    'conversation_id' => 'cnv_019fixture000000000000000001',
                    'from' => '5511999990101',
                    'type' => 'text',
                    'text' => 'Sua mensagem foi enviada.',
                    'contact_name' => 'Ana Ribeiro',
                ],
            ],
            ZapEventType::MessageFailed => [
                'id' => 'evt_019fixture000000000000000004',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [
                    'instance_id' => 'ins_019fixture000000000000000001',
                    'message_id' => 'msg_019fixture000000000000000003',
                    'conversation_id' => 'cnv_019fixture000000000000000001',
                    'from' => '5511999990101',
                    'type' => 'text',
                    'text' => 'Falha ao enviar.',
                    'contact_name' => 'Ana Ribeiro',
                ],
            ],
            ZapEventType::InstanceConnected => [
                'id' => 'evt_019fixture000000000000000005',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [
                    'instance_id' => 'ins_019fixture000000000000000001',
                    'status' => 'connected',
                    'phone_number' => '5511999999999',
                ],
            ],
            ZapEventType::InstanceDisconnected => [
                'id' => 'evt_019fixture000000000000000006',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [
                    'instance_id' => 'ins_019fixture000000000000000001',
                    'status' => 'disconnected',
                    'phone_number' => '5511999999999',
                ],
            ],
            ZapEventType::InstanceQrUpdated => [
                'id' => 'evt_019fixture000000000000000007',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [
                    'instance_id' => 'ins_019fixture000000000000000001',
                    'status' => 'waiting_qr',
                    'expires_at' => '2026-09-07T13:01:00+00:00',
                ],
            ],
            default => [
                'id' => 'evt_019fixture000000000000000000',
                'type' => $type->value,
                'created_at' => $createdAt,
                'data' => [],
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{path: string, value_mode: string, value: string}>
     */
    public function leafRows(array $payload, string $prefix = ''): array
    {
        $rows = [];

        foreach ($payload as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && $value !== [] && ! array_is_list($value)) {
                $rows = [...$rows, ...$this->leafRows($value, $path)];

                continue;
            }

            $rows[] = [
                'path' => $path,
                'value_mode' => 'field',
                'value' => $path,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{path: string, type: string}>
     */
    public function chips(ZapEventType $type): array
    {
        $fixture = $this->fixture($type);
        $chips = [];

        foreach ($this->aliases($type) as $alias => $canonical) {
            $value = Arr::get($fixture, $canonical);
            $chips[] = [
                'path' => $alias,
                'type' => is_int($value) || is_float($value) ? 'number' : (str_contains($alias, 'timestamp') || str_contains($alias, 'created_at') || str_contains($alias, 'expires_at') ? 'datetime' : 'string'),
            ];
        }

        return $chips;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function eventOptions(): array
    {
        return array_map(
            fn (ZapEventType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ],
            ZapEventType::customerFacing(),
        );
    }
}
