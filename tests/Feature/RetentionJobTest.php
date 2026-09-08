<?php

use App\Jobs\EnforceRetention;
use App\Models\ApiRequest;
use App\Models\Conversation;
use App\Models\Instance;
use App\Models\MediaObject;
use App\Models\Message;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Storage;

it('deletes expired inbox rows, media files, webhook events, and idempotency records', function () {
    Storage::fake('media');
    $this->freezeTime();

    $owner = User::factory()->withWorkspace()->create();
    $workspace = $owner->ownedWorkspace;
    $instance = Instance::factory()->for($workspace)->connected()->create();
    $conversation = Conversation::factory()->for($instance)->create();
    $stale = Message::factory()->for($conversation)->create([
        'occurred_at' => now()->subDays(91),
        'body' => 'old',
    ]);
    $fresh = Message::factory()->for($conversation)->create([
        'occurred_at' => now()->subDay(),
        'body' => 'new',
    ]);
    $media = MediaObject::factory()->for($stale)->expired()->create(['disk_path' => 'gone']);
    Storage::disk('media')->put($media->disk_path, 'bytes');

    WebhookEvent::factory()->for($instance)->create([
        'workspace_id' => $workspace->id,
        'expires_at' => now()->subMinute(),
    ]);
    $token = PersonalAccessToken::factory()->for($owner, 'tokenable')->create([
        'workspace_id' => $workspace->id,
    ]);
    ApiRequest::factory()->create([
        'workspace_id' => $workspace->id,
        'api_token_id' => $token->id,
        'expires_at' => now()->subMinute(),
    ]);

    (new EnforceRetention)->handle();

    expect(Message::query()->whereKey($stale->id)->exists())->toBeFalse()
        ->and(Message::query()->whereKey($fresh->id)->exists())->toBeTrue()
        ->and(MediaObject::query()->whereKey($media->id)->exists())->toBeFalse()
        ->and(Storage::disk('media')->exists($media->disk_path))->toBeFalse()
        ->and(WebhookEvent::query()->count())->toBe(0)
        ->and(ApiRequest::query()->count())->toBe(0);
});
