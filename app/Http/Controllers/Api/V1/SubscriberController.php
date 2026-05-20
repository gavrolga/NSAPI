<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SubscriberController extends Controller
{
    #[OA\Get(
        path: '/subscribers/{id}/notifications',
        summary: 'История уведомлений подписчика',
        tags: ['Subscribers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Список уведомлений'),
            new OA\Response(response: 404, description: 'Подписчик не найден'),
        ]
    )]
    public function notifications(int $id): JsonResponse
    {
        $subscriber = Subscriber::findOrFail($id);

        $recipients = $subscriber->notificationRecipients()
            ->with('notification')
            ->latest()
            ->paginate(20);

        $data = $recipients->map(fn ($recipient) => [
            'notification_id' => $recipient->notification_id,
            'channel'         => $recipient->notification->channel,
            'message'         => $recipient->notification->message,
            'priority'        => $recipient->notification->priority,
            'status'          => $recipient->status,
            'attempts'        => $recipient->attempts,
            'last_error'      => $recipient->last_error,
            'sent_at'         => $recipient->sent_at,
            'created_at'      => $recipient->created_at,
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total'        => $recipients->total(),
                'per_page'     => $recipients->perPage(),
                'current_page' => $recipients->currentPage(),
                'last_page'    => $recipients->lastPage(),
            ],
        ]);
    }
}
