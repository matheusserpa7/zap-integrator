<?php

use App\Enums\ApiAbility;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\InstanceController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\WebhookEndpointController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:public-api')->group(function (): void {
    Route::get('/instances', [InstanceController::class, 'index'])
        ->middleware('api.ability:'.ApiAbility::InstancesRead->value)
        ->name('api.v1.instances.index');

    Route::post('/instances', [InstanceController::class, 'store'])
        ->middleware('api.ability:'.ApiAbility::InstancesWrite->value)
        ->name('api.v1.instances.store');

    Route::get('/instances/{instance}', [InstanceController::class, 'show'])
        ->middleware('api.ability:'.ApiAbility::InstancesRead->value)
        ->name('api.v1.instances.show');

    Route::get('/instances/{instance}/connection', [InstanceController::class, 'connection'])
        ->middleware('api.ability:'.ApiAbility::InstancesRead->value)
        ->name('api.v1.instances.connection');

    Route::post('/instances/{instance}/qr/refresh', [InstanceController::class, 'refreshQr'])
        ->middleware('api.ability:'.ApiAbility::InstancesWrite->value)
        ->name('api.v1.instances.qr.refresh');

    Route::post('/messages/text', [MessageController::class, 'storeText'])
        ->middleware('api.ability:'.ApiAbility::MessagesSend->value)
        ->name('api.v1.messages.text');

    Route::get('/conversations', [ConversationController::class, 'index'])
        ->middleware('api.ability:'.ApiAbility::MessagesRead->value)
        ->name('api.v1.conversations.index');

    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
        ->middleware('api.ability:'.ApiAbility::MessagesRead->value)
        ->name('api.v1.conversations.show');

    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])
        ->middleware('api.ability:'.ApiAbility::MessagesRead->value)
        ->name('api.v1.conversations.messages');

    Route::get('/media/{media}', [MediaController::class, 'show'])
        ->middleware('api.ability:'.ApiAbility::MediaRead->value)
        ->name('api.v1.media.show');

    Route::get('/webhook-endpoints', [WebhookEndpointController::class, 'index'])
        ->middleware('api.ability:'.ApiAbility::WebhooksRead->value)
        ->name('api.v1.webhook-endpoints.index');

    Route::post('/webhook-endpoints', [WebhookEndpointController::class, 'store'])
        ->middleware('api.ability:'.ApiAbility::WebhooksWrite->value)
        ->name('api.v1.webhook-endpoints.store');

    Route::patch('/webhook-endpoints/{endpoint}', [WebhookEndpointController::class, 'update'])
        ->middleware('api.ability:'.ApiAbility::WebhooksWrite->value)
        ->name('api.v1.webhook-endpoints.update');

    Route::delete('/webhook-endpoints/{endpoint}', [WebhookEndpointController::class, 'destroy'])
        ->middleware('api.ability:'.ApiAbility::WebhooksWrite->value)
        ->name('api.v1.webhook-endpoints.destroy');

    Route::post('/webhook-endpoints/{endpoint}/test', [WebhookEndpointController::class, 'test'])
        ->middleware('api.ability:'.ApiAbility::WebhooksWrite->value)
        ->name('api.v1.webhook-endpoints.test');

    Route::post('/webhook-endpoints/{endpoint}/duplicate', [WebhookEndpointController::class, 'duplicate'])
        ->middleware('api.ability:'.ApiAbility::WebhooksWrite->value)
        ->name('api.v1.webhook-endpoints.duplicate');

    Route::get('/webhook-deliveries', [WebhookEndpointController::class, 'deliveries'])
        ->middleware('api.ability:'.ApiAbility::WebhooksRead->value)
        ->name('api.v1.webhook-deliveries.index');

    Route::post('/webhook-deliveries/{delivery}/retry', [WebhookEndpointController::class, 'retry'])
        ->middleware('api.ability:'.ApiAbility::WebhooksWrite->value)
        ->name('api.v1.webhook-deliveries.retry');
});
