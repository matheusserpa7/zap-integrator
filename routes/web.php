<?php

use App\Http\Controllers\ApiKeysController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\InstanceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebhookBuilderController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/settings', SettingsController::class)->name('settings');
    Route::get('/workspaces/{workspace}', WorkspaceController::class)->name('workspaces.show');
});

Route::middleware(['auth', 'active', 'workspace'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::get('/inbox/{conversation}', [InboxController::class, 'show'])->name('inbox.show');
    Route::get('/instances', [InstanceController::class, 'index'])->name('instances.index');
    Route::get('/instances/create', [InstanceController::class, 'create'])->name('instances.create');
    Route::post('/instances', [InstanceController::class, 'store'])->name('instances.store');
    Route::get('/instances/{instance}', [InstanceController::class, 'show'])->name('instances.show');
    Route::post('/instances/{instance}/qr', [InstanceController::class, 'refreshQr'])->name('instances.qr.refresh');
    Route::post('/instances/{instance}/disconnect', [InstanceController::class, 'disconnect'])->name('instances.disconnect');
    Route::delete('/instances/{instance}', [InstanceController::class, 'destroy'])->name('instances.destroy');
    Route::get('/webhooks', [WebhookBuilderController::class, 'index'])->name('webhooks.index');
    Route::get('/webhooks/builder', [WebhookBuilderController::class, 'create'])->name('webhooks.builder');
    Route::post('/webhooks', [WebhookBuilderController::class, 'store'])->name('webhooks.store');
    Route::post('/webhooks/test', [WebhookBuilderController::class, 'testDraft'])->name('webhooks.test-draft');
    Route::get('/webhooks/{endpoint}', [WebhookBuilderController::class, 'show'])->name('webhooks.show');
    Route::patch('/webhooks/{endpoint}', [WebhookBuilderController::class, 'update'])->name('webhooks.update');
    Route::delete('/webhooks/{endpoint}', [WebhookBuilderController::class, 'destroy'])->name('webhooks.destroy');
    Route::post('/webhooks/{endpoint}/duplicate', [WebhookBuilderController::class, 'duplicate'])->name('webhooks.duplicate');
    Route::post('/webhooks/{endpoint}/test', [WebhookBuilderController::class, 'test'])->name('webhooks.test');
    Route::post('/webhooks/deliveries/{delivery}/retry', [WebhookBuilderController::class, 'retry'])->name('webhooks.deliveries.retry');
    Route::get('/api-keys', [ApiKeysController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeysController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{apiKey}', [ApiKeysController::class, 'destroy'])->name('api-keys.destroy');
});
