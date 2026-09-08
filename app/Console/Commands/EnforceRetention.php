<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\EnforceRetention as EnforceRetentionJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('zap:enforce-retention')]
#[Description('Delete inbox rows, media files, webhook payloads, and idempotency records that have exceeded retention.')]
final class EnforceRetention extends Command
{
    public function handle(): int
    {
        EnforceRetentionJob::dispatch();

        $this->info('Dispatched retention job.');

        return self::SUCCESS;
    }
}
