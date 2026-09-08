<?php

use App\Http\Controllers\Platform\AllowlistController;
use App\Http\Controllers\Platform\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'platform'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::get('allowlist', [AllowlistController::class, 'index'])->name('allowlist.index');
        Route::post('allowlist', [AllowlistController::class, 'store'])->name('allowlist.store');
        Route::delete('allowlist/{emailAllowlist}', [AllowlistController::class, 'destroy'])->name('allowlist.destroy');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/disable', [UserController::class, 'disable'])->name('users.disable');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
