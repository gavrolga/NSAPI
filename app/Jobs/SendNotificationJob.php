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

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly int $recipientId
    ) {}

    public function handle(): void
    {
        $recipient = NotificationRecipient::with(['notification', 'subscriber'])
            ->findOrFail($this->recipientId);

        $notification = $recipient->notification;
        $subscriber   = $recipient->subscriber;

        $gateway = app(NotificationGatewayInterface::class . ':' . $notification->channel);

        $recipient->increment('attempts');

        try {
            $to = $notification->channel === 'email'
                ? $subscriber->email
                : $subscriber->phone;

            $result = $gateway->send($to, $notification->message);

            if ($result->success) {
                $recipient->update([
                    'status'  => 'delivered',
                    'sent_at' => now(),
                ]);

                // Обновляем статус уведомления если все получатели доставлены
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
            } else {
                $this->handleFailure($recipient, $result->error);
            }
        } catch (Throwable $e) {
            $this->handleFailure($recipient, $e->getMessage());
            throw $e;
        }
    }

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
            'status'     => 'queued',
            'last_error' => $error,
        ]);
    }
}
