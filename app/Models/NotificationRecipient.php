<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HigherOrderCollectionProxy;

class NotificationRecipient extends Model
{
    use HasFactory;

    /**
     * @var HigherOrderCollectionProxy|mixed
     */
    public mixed $notification;
    /**
     * @var HigherOrderCollectionProxy|mixed
     */
    public mixed $subscriber;
    protected $fillable = [
        'notification_id',
        'subscriber_id',
        'status',
        'attempts',
        'last_error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at'  => 'datetime',
        'attempts' => 'integer',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }
}
