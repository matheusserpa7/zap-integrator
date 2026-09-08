<?php

use App\Http\Controllers\Internal\EvolutionWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/internal/webhooks/evolution/{instancePublicId}', EvolutionWebhookController::class)
    ->middleware('throttle:evolution-webhooks')
    ->name('internal.webhooks.evolution');
