<?php

declare(strict_types=1);

namespace App\Domain\Platform\Actions;

use App\Enums\InstanceStatus;
use App\Enums\WebhookDeliveryStatus;
use App\Models\Instance;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\Workspace;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class GetDashboardMetrics
{
    /**
     * Queues Horizon supervises. Used only when Redis is the queue connection.
     *
     * @var list<string>
     */
    private const QUEUES = ['critical', 'webhooks', 'provider', 'media', 'maintenance', 'default'];

    /**
     * @return array{
     *     instanceCount: int,
     *     connectedCount: int,
     *     webhookDeliveredCount: int,
     *     webhookRetryingCount: int,
     *     webhookDeadLetterCount: int,
     *     queueDepth: int|null,
     *     canCreateInstance: bool,
     *     primaryInstanceUrl: string|null
     * }
     */
    public function __invoke(User $user, Workspace $workspace): array
    {
        $instanceRow = Instance::query()
            ->where('workspace_id', $workspace->id)
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('coalesce(sum(case when status = ? then 1 else 0 end), 0) as connected', [
                InstanceStatus::Connected->value,
            ])
            ->first();

        $instanceCount = (int) ($instanceRow->total ?? 0);
        $connectedCount = (int) ($instanceRow->connected ?? 0);

        $deliveryRow = WebhookDelivery::query()
            ->where('workspace_id', $workspace->id)
            ->toBase()
            ->selectRaw('coalesce(sum(case when status = ? then 1 else 0 end), 0) as delivered', [
                WebhookDeliveryStatus::Delivered->value,
            ])
            ->selectRaw('coalesce(sum(case when status in (?, ?) then 1 else 0 end), 0) as retrying', [
                WebhookDeliveryStatus::Pending->value,
                WebhookDeliveryStatus::Retrying->value,
            ])
            ->selectRaw('coalesce(sum(case when status = ? then 1 else 0 end), 0) as dead_letter', [
                WebhookDeliveryStatus::DeadLetter->value,
            ])
            ->first();

        $primary = Instance::query()
            ->where('workspace_id', $workspace->id)
            ->orderByDesc('id')
            ->first();

        return [
            'instanceCount' => $instanceCount,
            'connectedCount' => $connectedCount,
            'webhookDeliveredCount' => (int) ($deliveryRow->delivered ?? 0),
            'webhookRetryingCount' => (int) ($deliveryRow->retrying ?? 0),
            'webhookDeadLetterCount' => (int) ($deliveryRow->dead_letter ?? 0),
            'queueDepth' => $this->queueDepth(),
            'canCreateInstance' => $user->can('create', [Instance::class, $workspace])
                && $instanceCount < $workspace->maxInstancesLimit(),
            'primaryInstanceUrl' => $primary instanceof Instance ? route('instances.show', $primary) : null,
        ];
    }

    private function queueDepth(): ?int
    {
        if (config('queue.default') !== 'redis') {
            return null;
        }

        try {
            $depth = 0;

            foreach (self::QUEUES as $queue) {
                $depth += (int) Redis::llen('queues:'.$queue);
            }

            return $depth;
        } catch (Throwable) {
            return null;
        }
    }
}
