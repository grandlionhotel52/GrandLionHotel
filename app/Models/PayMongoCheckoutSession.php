<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayMongoCheckoutSession extends Model
{
    protected $table = 'paymongo_checkout_sessions';

    protected $primaryKey = 'paymongo_checkout_session_id';

    protected $fillable = ['booking_id', 'provider_session_id', 'checkout_url', 'amount_centavos', 'status'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }
}
