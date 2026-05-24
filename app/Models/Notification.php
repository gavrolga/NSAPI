<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    use HasUuids; use HasFactory;

    protected $fillable = [
        'idempotency_key',
        'channel',
        'message',
        'priority',
        'status',
        'id',
    ];

    protected $casts = [
        'channel'  => 'string',
        'priority' => 'string',
        'status'   => 'string',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class);
    }
}
