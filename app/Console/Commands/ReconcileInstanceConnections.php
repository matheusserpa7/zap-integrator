<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InstanceStatus;
use App\Jobs\SyncInstanceConnection;
use App\Models\Instance;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('instances:reconcile-connections')]
#[Description('Compare ZAP connection state with the messaging provider for recently active instances.')]
final class ReconcileInstanceConnections extends Command
{
    public function handle(): int
    {
        $minutes = (int) config('zap.instances.reconciliation_recent_minutes', 360);
        $cutoff = now()->subMinutes($minutes);
        $dispatched = 0;

        Instance::query()
            ->whereNotIn('status', [
                InstanceStatus::Creating,
                InstanceStatus::Deleting,
                InstanceStatus::Error,
            ])
            ->where(function (Builder $query) use ($cutoff): void {
                $query->whereIn('status', [
                    InstanceStatus::Connected,
                    InstanceStatus::WaitingQr,
                    InstanceStatus::Connecting,
                ])->orWhere('last_seen_at', '>=', $cutoff);
            })
            ->orderBy('id')
            ->chunkById(100, function ($instances) use (&$dispatched): void {
                foreach ($instances as $instance) {
                    SyncInstanceConnection::dispatch($instance->id)->onQueue('maintenance');
                    $dispatched++;
                }
            });

        $this->info("Dispatched {$dispatched} connection reconciliation job(s).");

        return self::SUCCESS;
    }
}
