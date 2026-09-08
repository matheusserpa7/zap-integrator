<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Instance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instance_id' => Instance::factory(),
            'wa_id' => '55'.fake()->unique()->numerify('###########'),
            'display_name' => fake()->name(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Contact $contact): void {
            $instance = $contact->instance;

            if ($instance instanceof Instance) {
                $contact->workspace_id = $instance->workspace_id;
            }
        });
    }
}
