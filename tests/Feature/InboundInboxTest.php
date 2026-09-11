<?php

use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Enums\MessageType;
use App\Events\MessageReceived;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Instance;
use App\Models\MediaObject;
use App\Models\Message;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\FakeMessagingProvider;

it('creates an inbox row from an inbound text fixture', function () {
    Event::fake([MessageReceived::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-messages-upsert'))
        ->assertAccepted();

    $message = Message::query()->first();

    expect($message)->not->toBeNull()
        ->and($message?->body)->toBe('MESSAGE_BODY_MUST_NOT_BE_STORED')
        ->and($message?->type)->toBe(MessageType::Text)
        ->and(Contact::query()->where('wa_id', '5511888888888')->exists())->toBeTrue()
        ->and(Conversation::query()->count())->toBe(1)
        ->and(WebhookEvent::query()->where('type', 'message.received')->exists())->toBeTrue();

    Http::assertNothingSent();
    Event::assertDispatched(MessageReceived::class, function (MessageReceived $event) use ($owner, $message): bool {
        return $event->workspacePublicId === $owner->ownedWorkspace?->public_id
            && $event->messagePublicId === $message?->public_id
            && $event->body === 'MESSAGE_BODY_MUST_NOT_BE_STORED';
    });
});

it('creates a placeholder and media object from an inbound image fixture using a fake downloader', function () {
    Storage::fake('media');
    Http::preventStrayRequests();

    $fake = new FakeMessagingProvider;
    $this->app->instance(MessagingProvider::class, $fake);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();

    postEvolutionWebhook($instance, evolutionFixture('webhook-messages-upsert-image'))
        ->assertAccepted();

    $message = Message::query()->first();
    $media = MediaObject::query()->first();

    expect($message?->body)->toBe('[image]')
        ->and($message?->type)->toBe(MessageType::Image)
        ->and($media)->not->toBeNull()
        ->and($media?->mime_type)->toBe('image/jpeg')
        ->and($media?->filename)->toBe('photo.jpg')
        ->and($message?->media_id)->toBe($media?->id);

    expect(Storage::disk('media')->get((string) $media?->disk_path))->toBe('fake-image-bytes');

    $event = WebhookEvent::query()->first();
    $encoded = json_encode($event?->payload);

    expect($encoded)->toContain('med_')
        ->and($encoded)->not->toContain('evolution')
        ->and($encoded)->not->toContain('EVO_APIKEY_MUST_NOT_BE_STORED')
        ->and($encoded)->not->toContain('http://');
});

it('ignores group chats', function () {
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $payload = evolutionFixture('webhook-messages-upsert');
    $payload['data']['key']['remoteJid'] = '120363@g.us';

    postEvolutionWebhook($instance, $payload)->assertAccepted();

    expect(Message::query()->count())->toBe(0)
        ->and(Conversation::query()->count())->toBe(0);
});
