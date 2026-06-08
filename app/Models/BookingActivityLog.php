<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingActivityLog extends Model
{
    protected $fillable = [
        'booking_id',
        'actor_id',
        'event_key',
        'action_label',
        'old_status',
        'new_status',
        'note',
        'metadata',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'acted_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
