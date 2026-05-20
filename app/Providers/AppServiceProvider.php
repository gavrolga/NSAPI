<?php

namespace App\Providers;

use App\Services\Gateways\EmailGatewayStub;
use App\Services\Gateways\NotificationGatewayInterface;
use App\Services\Gateways\SmsGatewayStub;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем шлюзы по ключу channel
        $this->app->bind(
            NotificationGatewayInterface::class . ':email',
            EmailGatewayStub::class
        );

        $this->app->bind(
            NotificationGatewayInterface::class . ':sms',
            SmsGatewayStub::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
