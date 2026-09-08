<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ApiRequest;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\MediaObject;
use App\Models\Message;
use App\Models\ProviderEventFingerprint;
use App\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class EnforceRetention implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 600;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function uniqueId(): string
    {
        return 'enforce-retention';
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['maintenance', 'retention'];
    }

    public function handle(): void
    {
        $this->purgeMedia();
        $this->purgeInbox();
        $this->purgeWebhookEvents();
        $this->purgeIdempotency();
        $this->purgeFingerprints();
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception instanceof Throwable) {
            report($exception);
        }
    }

    private function purgeMedia(): void
    {
        $disk = (string) config('zap.media.disk', 'media');

        MediaObject::query()
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($mediaObjects) use ($disk): void {
                foreach ($mediaObjects as $media) {
                    Storage::disk($disk)->delete($media->disk_path);
                    $media->delete();
                }
            });
    }

    private function purgeInbox(): void
    {
        $cutoff = now()->subDays(max(1, (int) config('zap.inbox.retention_days', 90)));
        $disk = (string) config('zap.media.disk', 'media');

        Message::query()
            ->where('occurred_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($messages) use ($disk): void {
                foreach ($messages as $message) {
                    $media = $message->media;

                    if ($media instanceof MediaObject) {
                        Storage::disk($disk)->delete($media->disk_path);
                        $media->delete();
                    }

                    $message->delete();
                }
            });

        Conversation::query()
            ->whereDoesntHave('messages')
            ->orderBy('id')
            ->chunkById(100, function ($conversations): void {
                foreach ($conversations as $conversation) {
                    $conversation->delete();
                }
            });

        Contact::query()
            ->whereDoesntHave('conversations')
            ->orderBy('id')
            ->chunkById(100, function ($contacts): void {
                foreach ($contacts as $contact) {
                    $contact->delete();
                }
            });
    }

    private function purgeWebhookEvents(): void
    {
        WebhookEvent::query()
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($events): void {
                foreach ($events as $event) {
                    $event->delete();
                }
            });
    }

    private function purgeIdempotency(): void
    {
        ApiRequest::query()
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    $request->delete();
                }
            });
    }

    private function purgeFingerprints(): void
    {
        ProviderEventFingerprint::query()
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($fingerprints): void {
                foreach ($fingerprints as $fingerprint) {
                    $fingerprint->delete();
                }
            });
    }
}
