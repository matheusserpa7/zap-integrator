<?php

use App\Enums\ApiAbility;
use App\Models\Conversation;
use App\Models\Instance;
use App\Models\MediaObject;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('downloads media for a token with media:read', function () {
    Storage::fake('media');

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $message = Message::factory()->for(
        Conversation::factory()->for($instance),
    )->image()->create();
    $media = MediaObject::factory()->for($message)->create([
        'disk_path' => 'med_file',
        'filename' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
    ]);
    Storage::disk('media')->put($media->disk_path, 'image-bytes');
    $created = createWorkspaceApiToken($owner, [ApiAbility::MediaRead->value]);

    $this->withToken($created->plainTextToken)
        ->get(route('api.v1.media.show', $media))
        ->assertOk()
        ->assertHeader('content-type', 'image/jpeg');
});

it('returns 403 when the token is missing media:read', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $message = Message::factory()->for(
        Conversation::factory()->for($instance),
    )->image()->create();
    $media = MediaObject::factory()->for($message)->create();
    $created = createWorkspaceApiToken($owner, [ApiAbility::MessagesRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.media.show', $media))
        ->assertForbidden()
        ->assertJsonPath('error.code', 'missing_ability');
});

it('returns 404 when media belongs to another workspace', function () {
    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $message = Message::factory()->for(
        Conversation::factory()->for($instance),
    )->image()->create();
    $media = MediaObject::factory()->for($message)->create();
    $stranger = User::factory()->withWorkspace()->create();
    $created = createWorkspaceApiToken($stranger, [ApiAbility::MediaRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.media.show', $media))
        ->assertNotFound()
        ->assertJsonPath('error.code', 'not_found');
});

it('returns 410 when the media object has expired', function () {
    Storage::fake('media');

    $owner = User::factory()->withWorkspace()->create();
    $instance = Instance::factory()->for($owner->ownedWorkspace)->connected()->create();
    $message = Message::factory()->for(
        Conversation::factory()->for($instance),
    )->image()->create();
    $media = MediaObject::factory()->for($message)->expired()->create([
        'disk_path' => 'med_expired',
    ]);
    Storage::disk('media')->put($media->disk_path, 'image-bytes');
    $created = createWorkspaceApiToken($owner, [ApiAbility::MediaRead->value]);

    $this->withToken($created->plainTextToken)
        ->getJson(route('api.v1.media.show', $media))
        ->assertStatus(410)
        ->assertJsonPath('error.code', 'media_expired');
});
