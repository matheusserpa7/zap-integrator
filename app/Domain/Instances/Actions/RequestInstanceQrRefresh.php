<?php

declare(strict_types=1);

namespace App\Domain\Instances\Actions;

use App\Domain\Instances\Exceptions\InstanceAlreadyConnected;
use App\Enums\InstanceStatus;
use App\Jobs\RefreshInstanceQr;
use App\Models\Instance;

final class RequestInstanceQrRefresh
{
    public function __invoke(Instance $instance): void
    {
        if ($instance->status === InstanceStatus::Connected) {
            throw new InstanceAlreadyConnected;
        }

        if ($instance->status->isBusy()) {
            return;
        }

        RefreshInstanceQr::dispatch($instance->id);
    }
}
