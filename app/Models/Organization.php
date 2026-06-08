<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'ketua_user_id',
        'kaprodi_user_id',
        'pembina_user_id',
        'is_active',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function ketua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_user_id');
    }

    public function kaprodi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kaprodi_user_id');
    }

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembina_user_id');
    }
}
