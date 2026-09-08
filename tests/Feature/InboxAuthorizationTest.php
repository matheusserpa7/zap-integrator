<?php

use App\Models\Conversation;
use App\Models\Instance;
use App\Models\Message;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the inbox split view for a workspace owner', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();
    Message::factory()->for($conversation)->create(['body' => 'Olá do inbox']);

    $this->actingAs($owner)
        ->get(route('inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inbox/Index')
            ->has('conversations', 1)
            ->where('conversations.0.public_id', $conversation->public_id)
            ->where('conversations.0.preview', 'Olá do inbox')
            ->missing('conversations.0.provider_instance_name'));
});

it('renders a conversation thread by public id', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();
    Message::factory()->for($conversation)->create(['body' => 'Thread']);

    $this->actingAs($owner)
        ->get(route('inbox.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inbox/Show')
            ->where('selected.public_id', $conversation->public_id)
            ->where('messages.0.body', 'Thread')
            ->missing('messages.0.provider_message_id'));
});

it('returns 404 when another workspace conversation is opened', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();
    $stranger = User::factory()->withWorkspace()->create();

    $this->actingAs($stranger)
        ->get(route('inbox.show', $conversation))
        ->assertNotFound();
});

it('does not expose sequential conversation ids in the inbox url', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();

    $this->actingAs($owner)
        ->get('/inbox/'.$conversation->id)
        ->assertNotFound();
});
