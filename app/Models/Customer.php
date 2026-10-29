<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Account
{
    protected $table = 'customers';

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'phone',
        'address_line',
        'street_address_line_2',
        'postal_code',
        'city',
        'province',
        'country',
        'email_verified_at',
        'password_changed_at',
        'remember_token',
        'is_active',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id', 'customer_id');
    }

    public function roomReviews(): HasManyThrough
    {
        return $this->hasManyThrough(RoomReview::class, Booking::class, 'customer_id', 'booking_id', 'customer_id', 'booking_id');
    }

}
