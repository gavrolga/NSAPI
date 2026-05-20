<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id'              => Str::uuid(),
            'idempotency_key' => Str::uuid(),
            'channel'         => fake()->randomElement(['email', 'sms']),
            'message'         => fake()->sentence(),
            'priority'        => fake()->randomElement(['high', 'low']),
            'status'          => 'queued',
        ];
    }

    public function high(): static
    {
        return $this->state(['priority' => 'high']);
    }

    public function low(): static
    {
        return $this->state(['priority' => 'low']);
    }
}
