<?php

use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Запуск массовой рассылки
    Route::post('/notifications', [NotificationController::class, 'send']);

    // История уведомлений подписчика
    Route::get('/subscribers/{id}/notifications', [SubscriberController::class, 'notifications']);
});
