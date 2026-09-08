<?php

use App\Domain\Platform\AssertAdminPasswordIsSafe;

it('treats empty and well-known admin passwords as unsafe', function (string $password) {
    $guard = new AssertAdminPasswordIsSafe(app());

    expect($guard->isUnsafe($password))->toBeTrue();
})->with([
    'empty' => [''],
    'password' => ['password'],
    'admin' => ['admin'],
    'secret' => ['secret'],
    '123456' => ['123456'],
    'changeme' => ['changeme'],
    'uppercase default' => ['PASSWORD'],
]);

it('accepts a strong admin password', function () {
    $guard = new AssertAdminPasswordIsSafe(app());

    expect($guard->isUnsafe('a-strong-admin-password'))->toBeFalse();
});

it('does not refuse to boot in the testing environment', function () {
    config(['zap.admin.password' => 'password']);

    expect(fn () => app(AssertAdminPasswordIsSafe::class)())->not->toThrow(RuntimeException::class);
});

it('refuses to boot in production when the admin password is a well-known default', function () {
    $previous = $this->app['env'];
    $this->app['env'] = 'production';
    config(['zap.admin.password' => 'password']);

    try {
        expect(fn () => app(AssertAdminPasswordIsSafe::class)())
            ->toThrow(RuntimeException::class, 'Refusing to boot: ADMIN_PASSWORD is empty or uses a well-known default.');
    } finally {
        $this->app['env'] = $previous;
    }
});
