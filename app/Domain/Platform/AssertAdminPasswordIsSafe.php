<?php

declare(strict_types=1);

namespace App\Domain\Platform;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use RuntimeException;

final class AssertAdminPasswordIsSafe
{
    /**
     * @var list<string>
     */
    public const WELL_KNOWN_DEFAULTS = [
        'password',
        'admin',
        'secret',
        '123456',
        'admin123',
        'changeme',
        'zap',
        'zap123',
        'password123',
        'qwerty',
        'letmein',
        'default',
    ];

    public function __construct(private Application $app) {}

    public function isUnsafe(string $password): bool
    {
        if ($password === '') {
            return true;
        }

        return in_array(Str::lower($password), self::WELL_KNOWN_DEFAULTS, true);
    }

    public function __invoke(): void
    {
        if ($this->app->environment(['local', 'testing'])) {
            return;
        }

        if ($this->isUnsafe((string) config('zap.admin.password'))) {
            throw new RuntimeException('Refusing to boot: ADMIN_PASSWORD is empty or uses a well-known default.');
        }
    }
}
