<?php

namespace Tests\Feature\Api\V1;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Subscriber;
use App\Services\Gateways\EmailGatewayStub;
use App\Services\Gateways\NotificationGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Queue;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Чистим Redis перед каждым тестом
        Redis::flushdb();
    }

    // Тест 1: POST создаёт уведомление со статусом queued
    public function test_bulk_notification_is_queued(): void
    {
        // Отключаем выполнение джобов чтобы проверить именно статус queued
        Queue::fake();

        $subscribers = Subscriber::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/notifications', [
            'channel'         => 'email',
            'message'         => 'Ваш код: 1234',
            'priority'        => 'high',
            'idempotency_key' => 'test-key-001',
            'recipient_ids'   => $subscribers->pluck('id')->toArray(),
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'notification_id',
                'channel',
                'priority',
                'status',
                'recipients_count',
                'message',
            ])
            ->assertJsonFragment(['status' => 'queued'])
            ->assertJsonFragment(['recipients_count' => 2]);

        $this->assertDatabaseHas('notifications', [
            'idempotency_key' => 'test-key-001',
            'channel'         => 'email',
        ]);

        $this->assertDatabaseCount('notification_recipients', 2);

        // Проверяем что джоб был поставлен в очередь
        Queue::assertPushed(SendNotificationJob::class, 2);
    }

    // Тест 2: Дедупликация — повторный запрос не создаёт дубль
    public function test_idempotency_prevents_duplicate(): void
    {
        $subscriber = Subscriber::factory()->create();

        $payload = [
            'channel'         => 'email',
            'message'         => 'Тест дедупликации',
            'priority'        => 'low',
            'idempotency_key' => 'unique-key-dup',
            'recipient_ids'   => [$subscriber->id],
        ];

        $first  = $this->postJson('/api/v1/notifications', $payload);
        $second = $this->postJson('/api/v1/notifications', $payload);

        $first->assertStatus(202);
        $second->assertStatus(200);

        // Одинаковый notification_id в обоих ответах
        $this->assertEquals(
            $first->json('notification_id'),
            $second->json('notification_id')
        );

        // В БД только одна запись
        $this->assertDatabaseCount('notifications', 1);
    }

    // Тест 3: Валидация — неверный channel
    public function test_validation_fails_for_invalid_channel(): void
    {
        $subscriber = Subscriber::factory()->create();

        $response = $this->postJson('/api/v1/notifications', [
            'channel'         => 'telegram',
            'message'         => 'Test',
            'idempotency_key' => 'key-123',
            'recipient_ids'   => [$subscriber->id],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);
    }

    // Тест 4: Валидация — несуществующий subscriber
    public function test_validation_fails_for_nonexistent_subscriber(): void
    {
        $response = $this->postJson('/api/v1/notifications', [
            'channel'         => 'sms',
            'message'         => 'Test',
            'idempotency_key' => 'key-456',
            'recipient_ids'   => [99999],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recipient_ids.0']);
    }

    // Тест 5: GET возвращает историю уведомлений подписчика
    public function test_subscriber_notification_history(): void
    {
        $subscriber   = Subscriber::factory()->create();
        $notification = Notification::factory()->create();

        NotificationRecipient::factory()->create([
            'subscriber_id'   => $subscriber->id,
            'notification_id' => $notification->id,
            'status'          => 'delivered',
        ]);

        $response = $this->getJson("/api/v1/subscribers/{$subscriber->id}/notifications");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['notification_id', 'channel', 'status', 'attempts']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ])
            ->assertJsonFragment(['status' => 'delivered']);
    }

    // Тест 6: GET возвращает 404 для несуществующего подписчика
    public function test_subscriber_not_found_returns_404(): void
    {
        $response = $this->getJson('/api/v1/subscribers/99999/notifications');
        $response->assertStatus(404);
    }
}
