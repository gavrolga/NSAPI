<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Log;

class EmailGatewayStub implements NotificationGatewayInterface
{
    public function send(string $to, string $message): GatewayResult
    {
        // Симулируем задержку реального провайдера
        usleep(random_int(100000, 300000)); // 0.1 - 0.3 сек

        // 10% вероятность ошибки для реализма
        if (random_int(1, 10) === 1) {
            Log::warning('EmailGatewayStub: simulated failure', ['to' => $to]);
            return GatewayResult::fail('Email provider temporarily unavailable.');
        }

        Log::info('EmailGatewayStub: message sent', [
            'to'      => $to,
            'message' => substr($message, 0, 50),
        ]);

        return GatewayResult::ok();
    }
}
