<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Микросервис массовых уведомлений',
    title: 'Notification Service API'
)]
#[OA\Server(
    url: '/api/v1',
    description: 'API v1'
)]
abstract class Controller
{
}
