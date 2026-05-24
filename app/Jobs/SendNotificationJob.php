<?php

namespace App\Jobs;

use App\Models\NotificationRecipient;
use App\Services\Gateways\NotificationGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Максимум попыток
    public int $tries = 3;

    // Пауза между попытками в секундах
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly int $recipientId
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $recipient = NotificationRecipient::with(['notification', 'subscriber'])
            ->findOrFail($this->recipientId);

        $notification = $recipient->notification;
        $subscriber   = $recipient->subscriber;

        // Определяем нужный шлюз через DI-контейнер
        $gateway = app(NotificationGatewayInterface::class . ':' . $notification->channel);

        // Обновляем счётчик попыток
        $recipient->increment('attempts');

        try {
            $to = $notification->channel === 'email'
                ? $subscriber->email
                : $subscriber->phone;

            $result = $gateway->send($to, $notification->message);

            if ($result->success) {
                // Успешно отправлено
                $recipient->update([
                    'status'  => 'delivered',
                    'sent_at' => now(),
                ]);
            } else {
                // Шлюз вернул ошибку — пробуем снова
                $this->handleFailure($recipient, $result->error);
            }

            if ($result->success) {
                $recipient->update([
                    'status'  => 'delivered',
                    'sent_at' => now(),
                ]);

                // Обновляем статус уведомления если все получатели доставлены
                $notification = $recipient->notification;
                $allDelivered = $notification->recipients()
                    ->whereNotIn('status', ['delivered', 'rejected'])
                    ->doesntExist();

                if ($allDelivered) {
                    $hasRejected = $notification->recipients()
                        ->where('status', 'rejected')
                        ->exists();

                    $notification->update([
                        'status' => $hasRejected ? 'sent' : 'delivered',
                    ]);
                }
            }
        } catch (Throwable $e) {
            $this->handleFailure($recipient, $e->getMessage());
            throw $e; // перебрасываем чтобы Laravel сделал retry
        }
    }

    // Вызывается когда все попытки исчерпаны
    public function failed(Throwable $e): void
    {
        Log::error('SendNotificationJob permanently failed', [
            'recipient_id' => $this->recipientId,
            'error'        => $e->getMessage(),
        ]);

        NotificationRecipient::where('id', $this->recipientId)
            ->update([
                'status'     => 'rejected',
                'last_error' => $e->getMessage(),
            ]);
    }

    private function handleFailure(NotificationRecipient $recipient, string $error): void
    {
        $recipient->update([
            'status'     => 'queued', // вернём в queued, retry сделает новую попытку
            'last_error' => $error,
        ]);
    }
}
