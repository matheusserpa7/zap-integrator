<?php

use App\Domain\Messaging\Actions\MarkOutboundMessageResult;
use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Enums\ApiAbility;
use App\Enums\InstanceStatus;
use App\Enums\MessageStatus;
use App\Events\MessageAccepted;
use App\Events\MessageFailed;
use App\Events\MessageSent;
use App\Jobs\SendOutboundText;
use App\Models\Instance;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\Fakes\FakeMessagingProvider;

function sendTextHeaders(string $idempotencyKey = 'send-1'): array
{
    return ['Idempotency-Key' => $idempotencyKey];
}

it('accepts outbound text with 202 and dispatches a provider job', function () {
    Event::fake([MessageAccepted::class]);
    Queue::fake([SendOutboundText::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Olá do ZAP',
        ], sendTextHeaders())
        ->assertAccepted()
        ->assertJsonPath('data.status', 'sending')
        ->assertJsonPath('data.body', 'Olá do ZAP')
        ->assertJsonPath('data.direction', 'outbound')
        ->assertJsonMissingPath('data.provider_instance_name');

    $message = Message::query()->first();

    expect($message)->not->toBeNull()
        ->and($message?->status)->toBe(MessageStatus::Sending)
        ->and($message?->conversation->contact->wa_id)->toBe('5511888888888');

    Queue::assertPushedOn('provider', SendOutboundText::class);
    Http::assertNothingSent();
    Event::assertDispatched(MessageAccepted::class, function (MessageAccepted $event) use ($owner, $message): bool {
        return $event->workspacePublicId === $owner->ownedWorkspace?->public_id
            && $event->messagePublicId === $message?->public_id
            && $event->status === 'sending';
    });
});

it('replays the stored 202 response for the same idempotency key and hash', function () {
    Queue::fake([SendOutboundText::class]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);
    $payload = [
        'instance_id' => $instance->public_id,
        'to' => '5511888888888',
        'text' => 'Olá do ZAP',
    ];

    $first = $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), $payload, sendTextHeaders('same-key'))
        ->assertAccepted();

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), $payload, sendTextHeaders('same-key'))
        ->assertAccepted()
        ->assertExactJson($first->json());

    expect(Message::query()->count())->toBe(1);
    Queue::assertPushed(SendOutboundText::class, 1);
});

it('returns 409 when the same idempotency key is reused with a different body', function () {
    Queue::fake([SendOutboundText::class]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Primeira',
        ], sendTextHeaders('same-key'))
        ->assertAccepted();

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Segunda',
        ], sendTextHeaders('same-key'))
        ->assertConflict()
        ->assertJsonPath('error.code', 'idempotency_conflict');

    expect(Message::query()->count())->toBe(1);
});

it('returns 422 when the Idempotency-Key header is missing', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Olá',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'missing_idempotency_key');
});

it('returns 422 when the instance is not connected', function () {
    Queue::fake([SendOutboundText::class]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->create([
        'status' => InstanceStatus::Disconnected,
    ]);
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Olá',
        ], sendTextHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'instance_not_connected');

    expect(Message::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('returns 403 when the token is missing messages:send', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesRead->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Olá',
        ], sendTextHeaders())
        ->assertForbidden()
        ->assertJsonPath('error.code', 'missing_ability');
});

it('returns 404 when sending through another workspace instance', function () {
    Queue::fake([SendOutboundText::class]);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $stranger = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($stranger, [ApiAbility::MessagesSend->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Olá',
        ], sendTextHeaders())
        ->assertNotFound();

    expect(Message::query()->count())->toBe(0);
});

it('marks the outbound message sent when the provider job succeeds', function () {
    Event::fake([MessageSent::class]);
    Http::preventStrayRequests();
    $this->app->instance(MessagingProvider::class, new FakeMessagingProvider);

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Olá do ZAP',
        ], sendTextHeaders('job-ok'))
        ->assertAccepted();

    $message = Message::query()->first();

    expect($message?->status)->toBe(MessageStatus::Sent)
        ->and($message?->provider_message_id)->toStartWith('FAKE_SENT_');

    Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($owner, $message): bool {
        return $event->workspacePublicId === $owner->ownedWorkspace?->public_id
            && $event->messagePublicId === $message?->public_id
            && $event->status === 'sent';
    });
});

it('marks the outbound message failed when the provider job exhausts retries', function () {
    Event::fake([MessageFailed::class]);
    Queue::fake([SendOutboundText::class]);
    Http::preventStrayRequests();

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);
    $provider = new FakeMessagingProvider;
    $provider->sendShouldFail = true;
    $this->app->instance(MessagingProvider::class, $provider);

    $this->withToken($created->plainTextToken)
        ->postJson(route('api.v1.messages.text'), [
            'instance_id' => $instance->public_id,
            'to' => '5511888888888',
            'text' => 'Olá do ZAP',
        ], sendTextHeaders('job-fail'))
        ->assertAccepted();

    $message = Message::query()->first();
    $job = new SendOutboundText((int) $message?->id);

    try {
        $job->handle(app(MessagingProvider::class), app(MarkOutboundMessageResult::class));
    } catch (RuntimeException $exception) {
        $job->failed($exception);
    }

    expect($message?->fresh()?->status)->toBe(MessageStatus::Failed);

    Event::assertDispatched(MessageFailed::class, function (MessageFailed $event) use ($owner, $message): bool {
        return $event->workspacePublicId === $owner->ownedWorkspace?->public_id
            && $event->messagePublicId === $message?->public_id
            && $event->status === 'failed';
    });
});
