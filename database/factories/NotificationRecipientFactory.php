<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationRecipientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'notification_id' => Notification::factory(),
            'subscriber_id'   => Subscriber::factory(),
            'status'          => 'queued',
            'attempts'        => 0,
            'last_error'      => null,
            'sent_at'         => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state([
            'status'   => 'delivered',
            'attempts' => 1,
            'sent_at'  => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'     => 'rejected',
            'attempts'   => 3,
            'last_error' => 'Provider unavailable.',
        ]);
    }
}
