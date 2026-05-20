<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class NotificationController extends Controller
{
    #[OA\Post(
        path: '/notifications',
        summary: 'Запустить массовую рассылку',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['channel', 'message', 'idempotency_key', 'recipient_ids'],
                properties: [
                    new OA\Property(property: 'channel', type: 'string', enum: ['email', 'sms'], example: 'email'),
                    new OA\Property(property: 'message', type: 'string', example: 'Ваш код: 1234'),
                    new OA\Property(property: 'priority', type: 'string', enum: ['high', 'low'], example: 'high'),
                    new OA\Property(property: 'idempotency_key', type: 'string', example: 'unique-key-001'),
                    new OA\Property(property: 'recipient_ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                ]
            )
        ),
        tags: ['Notifications'],
        responses: [
            new OA\Response(
                response: 202,
                description: 'Уведомление принято в очередь',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'notification_id', type: 'string'),
                        new OA\Property(property: 'channel', type: 'string'),
                        new OA\Property(property: 'priority', type: 'string'),
                        new OA\Property(property: 'status', type: 'string'),
                        new OA\Property(property: 'recipients_count', type: 'integer'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 200, description: 'Дубликат запроса'),
            new OA\Response(response: 422, description: 'Ошибка валидации'),
        ]
    )]
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $idempotencyKey = $request->input('idempotency_key');
        $cacheKey = 'idem:' . $idempotencyKey;

        $cached = Redis::get($cacheKey);
        if ($cached) {
            return response()->json(json_decode($cached, true), 200);
        }

        $notification = Notification::create([
            'id'              => Str::uuid(),
            'idempotency_key' => $idempotencyKey,
            'channel'         => $request->input('channel'),
            'message'         => $request->input('message'),
            'priority'        => $request->input('priority', 'low'),
            'status'          => 'queued',
        ]);

        $recipients = [];
        foreach ($request->input('recipient_ids') as $subscriberId) {
            $recipients[] = NotificationRecipient::create([
                'notification_id' => $notification->id,
                'subscriber_id'   => $subscriberId,
                'status'          => 'queued',
            ]);
        }

        $queue = $notification->priority === 'high' ? 'high' : 'low';
        foreach ($recipients as $recipient) {
            SendNotificationJob::dispatch($recipient->id)->onQueue($queue);
        }

        $response = [
            'notification_id'  => $notification->id,
            'channel'          => $notification->channel,
            'priority'         => $notification->priority,
            'status'           => $notification->status,
            'recipients_count' => count($recipients),
            'message'          => 'Notification queued successfully.',
        ];

        Redis::setex($cacheKey, config('app.idempotency_ttl', 86400), json_encode($response));

        return response()->json($response, 202);
    }
}
