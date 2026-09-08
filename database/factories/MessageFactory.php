<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'provider_message_id' => null,
            'direction' => MessageDirection::Inbound,
            'type' => MessageType::Text,
            'status' => MessageStatus::Received,
            'body' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Message $message): void {
            $conversation = $message->conversation;

            if ($conversation instanceof Conversation) {
                $message->workspace_id = $conversation->workspace_id;
                $message->instance_id = $conversation->instance_id;
            }
        });
    }

    public function outbound(): static
    {
        return $this->state(fn (array $attributes): array => [
            'direction' => MessageDirection::Outbound,
            'status' => MessageStatus::Sending,
        ]);
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MessageType::Image,
            'body' => MessageType::Image->placeholder(),
        ]);
    }
}
