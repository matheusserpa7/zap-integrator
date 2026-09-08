<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Messaging\Actions\MarkOutboundMessageResult;
use App\Domain\Messaging\Contracts\MessagingProvider;
use App\Domain\Messaging\Data\SendTextData;
use App\Domain\Messaging\Exceptions\ProviderRateLimited;
use App\Domain\Messaging\Exceptions\ProviderUnavailable;
use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendOutboundText implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 120;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 15, 30];

    public function __construct(public int $messageId)
    {
        $this->onQueue('provider');
    }

    public function uniqueId(): string
    {
        return 'send-text-'.$this->messageId;
    }

    /**
     * @return list<WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('message-'.$this->messageId)];
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['provider', 'message:'.$this->messageId];
    }

    public function handle(MessagingProvider $provider, MarkOutboundMessageResult $markResult): void
    {
        $message = Message::query()->with(['instance', 'conversation.contact'])->find($this->messageId);

        if ($message === null || $message->status !== MessageStatus::Sending) {
            return;
        }

        try {
            $result = $provider->sendText(new SendTextData(
                instanceName: $message->instance->provider_instance_name,
                to: $message->conversation->contact->wa_id,
                text: $message->body,
            ));
        } catch (ProviderRateLimited|ProviderUnavailable $exception) {
            throw $exception;
        }

        $markResult->sent($message, $result->providerMessageId);
    }

    public function failed(?Throwable $exception): void
    {
        $message = Message::query()->find($this->messageId);

        if ($message instanceof Message) {
            app(MarkOutboundMessageResult::class)->failed($message);
        }

        Log::warning('Outbound text send failed', [
            'message_id' => $this->messageId,
            'exception' => $exception instanceof Throwable ? $exception::class : null,
        ]);
    }
}
