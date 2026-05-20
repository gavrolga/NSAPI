<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Log;

class SmsGatewayStub implements NotificationGatewayInterface
{
    public function send(string $to, string $message): GatewayResult
    {
        // Симулируем задержку реального провайдера
        usleep(random_int(50000, 200000)); // 0.05 - 0.2 сек

        // 10% вероятность ошибки
        if (random_int(1, 10) === 1) {
            Log::warning('SmsGatewayStub: simulated failure', ['to' => $to]);
            return GatewayResult::fail('SMS provider temporarily unavailable.');
        }

        Log::info('SmsGatewayStub: message sent', [
            'to'      => $to,
            'message' => substr($message, 0, 50),
        ]);

        return GatewayResult::ok();
    }
}
