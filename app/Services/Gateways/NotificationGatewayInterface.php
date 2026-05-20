<?php

namespace App\Services\Gateways;

interface NotificationGatewayInterface
{
    public function send(string $to, string $message): GatewayResult;
}
