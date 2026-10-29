<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RoomReview extends Model
{
    protected $primaryKey = 'room_review_id';

    protected $fillable = [
        'booking_id',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function room(): HasOneThrough
    {
        return $this->hasOneThrough(Room::class, Booking::class, 'booking_id', 'room_id', 'booking_id', 'room_id');
    }

    public function customer(): HasOneThrough
    {
        return $this->hasOneThrough(Customer::class, Booking::class, 'booking_id', 'customer_id', 'booking_id', 'customer_id');
    }
}
