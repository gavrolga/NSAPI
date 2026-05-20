<?php

namespace Database\Seeders;

use App\Models\Subscriber;
use Illuminate\Database\Seeder;

class SubscriberSeeder extends Seeder
{
    public function run(): void
    {
        $subscribers = [
            [
                'name'  => 'Вася',
                'email' => 'vasily@example.com',
                'phone' => '+79001112233',
            ],
            [
                'name'  => 'Володя',
                'email' => 'vovololo@example.com',
                'phone' => '+79004445566',
            ],
            [
                'name'  => 'Сережа',
                'email' => 'sergay@example.com',
                'phone' => '+79007778899',
            ],
        ];

        foreach ($subscribers as $data) {
            Subscriber::firstOrCreate(['email' => $data['email']], $data);
        }
    }
}
