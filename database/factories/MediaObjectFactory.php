<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\MediaObject;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaObject>
 */
class MediaObjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => Message::factory()->image(),
            'type' => MessageType::Image,
            'mime_type' => 'image/jpeg',
            'filename' => 'photo.jpg',
            'size_bytes' => 204800,
            'checksum' => hash('sha256', 'fixture-bytes'),
            'disk_path' => 'med_fixture',
            'expires_at' => now()->addDays(7),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (MediaObject $media): void {
            $message = $media->message;

            if ($message instanceof Message) {
                $media->workspace_id = $message->workspace_id;
                $media->instance_id = $message->instance_id;

                if (blank($media->disk_path) || $media->disk_path === 'med_pending' || $media->disk_path === 'med_fixture') {
                    $media->disk_path = $media->public_id ?: 'med_pending';
                }
            }
        });
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
