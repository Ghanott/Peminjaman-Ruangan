<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'organization_id',
        'requester_id',
        'room_id',
        'event_name',
        'event_description',
        'event_date',
        'start_time',
        'end_time',
        'participant_count',
        'status',
        'current_step_order',
        'current_role_key',
        'revision_count',
        'last_revision_note',
        'submitted_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'submitted_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(BookingApproval::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BookingRevision::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(BookingActivityLog::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(BookingAttachment::class);
    }

    public function sprDocument(): HasOne
    {
        return $this->hasOne(SprDocument::class);
    }
}
