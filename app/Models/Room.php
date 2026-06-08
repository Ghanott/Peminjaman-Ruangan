<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'code',
        'name',
        'location',
        'capacity',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(RoomOpeningHour::class);
    }

    public function blackouts(): HasMany
    {
        return $this->hasMany(RoomBlackout::class);
    }
}
