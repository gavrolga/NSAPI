<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\Subscriber;
use App\Services\Gateways\GatewayResult;
use App\Services\Gateways\NotificationGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionException;
use Tests\TestCase;
use Throwable;

class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    // Тест 1: Job успешно доставляет и меняет статус на delivered

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function test_job_sends_and_updates_status_to_delivered(): void
    {
        // Мокаем gateway — всегда возвращает успех
        $this->app->bind(
            NotificationGatewayInterface::class . ':email',
            fn() => new class implements NotificationGatewayInterface {
                public function send(string $to, string $message): GatewayResult {
                    return GatewayResult::ok();
                }
            }
        );

        $subscriber   = Subscriber::factory()->create(['email' => 'test@example.com']);
        $notification = Notification::factory()->create(['channel' => 'email']);
        $recipient    = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'subscriber_id'   => $subscriber->id,
            'status'          => 'queued',
        ]);

        (new SendNotificationJob($recipient->id))->handle();

        $this->assertDatabaseHas('notification_recipients', [
            'id'     => $recipient->id,
            'status' => 'delivered',
        ]);

        $recipient->refresh();
        $this->assertEquals(1, $recipient->attempts);
        $this->assertNotNull($recipient->sent_at);
    }

    // Тест 2: Job помечает статус rejected после всех попыток
    public function test_job_marks_rejected_after_all_attempts_fail(): void
    {
        $subscriber   = Subscriber::factory()->create(['email' => 'fail@example.com']);
        $notification = Notification::factory()->create(['channel' => 'email']);
        $recipient    = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'subscriber_id'   => $subscriber->id,
            'status'          => 'queued',
        ]);

        $job = new SendNotificationJob($recipient->id);
        $job->failed(new \Exception('Provider permanently down.'));

        $this->assertDatabaseHas('notification_recipients', [
            'id'     => $recipient->id,
            'status' => 'rejected',
        ]);
    }

    // Тест 3: Job увеличивает attempts при каждом запуске

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function test_job_increments_attempts_on_each_run(): void
    {
        $this->app->bind(
            NotificationGatewayInterface::class . ':email',
            fn() => new class implements NotificationGatewayInterface {
                public function send(string $to, string $message): GatewayResult {
                    return GatewayResult::ok();
                }
            }
        );

        $subscriber   = Subscriber::factory()->create(['email' => 'test2@example.com']);
        $notification = Notification::factory()->create(['channel' => 'email']);
        $recipient    = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'subscriber_id'   => $subscriber->id,
            'status'          => 'queued',
            'attempts'        => 0,
        ]);

        (new SendNotificationJob($recipient->id))->handle();

        $recipient->refresh();
        $this->assertEquals(1, $recipient->attempts);
    }

    // Тест 4: Job успешно доставляет SMS
    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function test_job_sends_sms_and_updates_status(): void
    {
        $this->app->bind(
            NotificationGatewayInterface::class . ':sms',
            fn() => new class implements NotificationGatewayInterface {
                public function send(string $to, string $message): GatewayResult {
                    return GatewayResult::ok();
                }
            }
        );

        $subscriber   = Subscriber::factory()->create(['phone' => '+79001112233']);
        $notification = Notification::factory()->create(['channel' => 'sms']);
        $recipient    = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'subscriber_id'   => $subscriber->id,
            'status'          => 'queued',
        ]);

        (new SendNotificationJob($recipient->id))->handle();

        $this->assertDatabaseHas('notification_recipients', [
            'id'     => $recipient->id,
            'status' => 'delivered',
        ]);
    }

    // Тест 5: Job делает retry при ошибке gateway
    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    public function test_job_retries_on_gateway_failure(): void
    {
        $this->app->bind(
            NotificationGatewayInterface::class . ':email',
            fn() => new class implements NotificationGatewayInterface {
                public function send(string $to, string $message): GatewayResult {
                    return GatewayResult::fail('Provider temporarily unavailable.');
                }
            }
        );

        $subscriber   = Subscriber::factory()->create(['email' => 'retry@example.com']);
        $notification = Notification::factory()->create(['channel' => 'email']);
        $recipient    = NotificationRecipient::factory()->create([
            'notification_id' => $notification->id,
            'subscriber_id'   => $subscriber->id,
            'status'          => 'queued',
        ]);

        (new SendNotificationJob($recipient->id))->handle();

        $this->assertDatabaseHas('notification_recipients', [
            'id'         => $recipient->id,
            'status'     => 'queued',
            'last_error' => 'Provider temporarily unavailable.',
        ]);

        $recipient->refresh();
        $this->assertEquals(1, $recipient->attempts);
    }
}
