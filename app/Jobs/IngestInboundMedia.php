<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Media\Actions\StoreInboundMedia;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

final class IngestInboundMedia implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 60;

    public int $uniqueFor = 180;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(public int $messageId, public array $metadata = [])
    {
        $this->onQueue('media');
    }

    public function uniqueId(): string
    {
        return 'ingest-media-'.$this->messageId;
    }

    /**
     * @return list<WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('media-message-'.$this->messageId)];
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['media', 'message:'.$this->messageId];
    }

    public function handle(StoreInboundMedia $store): void
    {
        $message = Message::query()->with(['instance', 'media'])->find($this->messageId);

        if ($message === null || ! $message->type->hasMedia() || $message->media_id !== null) {
            return;
        }

        $store($message, $this->metadata);
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Inbound media ingest failed', [
            'message_id' => $this->messageId,
            'exception' => $exception instanceof Throwable ? $exception::class : null,
        ]);
    }
}
