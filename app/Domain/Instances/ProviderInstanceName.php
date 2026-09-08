<?php

declare(strict_types=1);

namespace App\Domain\Instances;

use App\Models\Instance;
use App\Models\Workspace;

final class ProviderInstanceName
{
    public static function make(Workspace $workspace, Instance $instance): string
    {
        return 'zap_'.$workspace->public_id.'_'.$instance->public_id;
    }
}
