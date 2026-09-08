<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Instance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instance_id' => Instance::factory(),
            'last_message_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Conversation $conversation): void {
            $contact = $conversation->contact;

            if ($contact instanceof Contact) {
                $conversation->workspace_id = $contact->workspace_id;
                $conversation->instance_id = $contact->instance_id;

                return;
            }

            $instance = $conversation->instance;

            if ($instance instanceof Instance) {
                $conversation->workspace_id = $instance->workspace_id;
                $contact = Contact::factory()->for($instance)->create();
                $conversation->contact_id = $contact->id;
                $conversation->setRelation('contact', $contact);
            }
        });
    }
}
