<?php

use App\Enums\ApiAbility;
use App\Enums\MessageDirection;
use App\Models\Conversation;
use App\Models\Instance;
use App\Models\Message;
use App\Models\User;

it('lists conversations for a token with messages:read', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();
    Message::factory()->for($conversation)->create(['body' => 'Olá']);
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.conversations.index'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $conversation->public_id)
        ->assertJsonPath('data.0.last_message.body', 'Olá')
        ->assertJsonMissingPath('data.0.id_internal');
});

it('returns 404 when another workspace conversation public id is guessed', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();
    $stranger = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($stranger, [ApiAbility::MessagesRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.conversations.show', $conversation))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');
});

it('lists messages without Evolution urls', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();
    Message::factory()->for($conversation)->create([
        'direction' => MessageDirection::Inbound,
        'body' => 'Oi',
    ]);
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesRead->value]);

    $response = $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.conversations.messages', $conversation))
        ->assertOk()
        ->assertJsonPath('data.0.body', 'Oi');

    expect(json_encode($response->json()))->not->toContain('evolution')
        ->and(json_encode($response->json()))->not->toContain('apikey');
});

it('returns 403 when the token is missing messages:read', function () {
    $owner = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesSend->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.conversations.index'))
        ->assertForbidden()
        ->assertJsonPath('error.code', 'missing_ability');
});
