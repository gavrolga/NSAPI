<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SubscriberController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Запуск массовой рассылки
    Route::post('/notifications', [NotificationController::class, 'send']);

    // История уведомлений подписчика
    Route::get('/subscribers/{id}/notifications', [SubscriberController::class, 'notifications']);
});
