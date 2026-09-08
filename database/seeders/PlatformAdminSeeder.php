<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Platform\Actions\SeedPlatformAdmin;
use Illuminate\Database\Seeder;

class PlatformAdminSeeder extends Seeder
{
    public function run(SeedPlatformAdmin $seedPlatformAdmin): void
    {
        $seedPlatformAdmin();
    }
}
