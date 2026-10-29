<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingBill extends Model
{
    protected $primaryKey = 'booking_bill_id';

    protected $fillable = [
        'booking_id', 'total_amount', 'original_amount', 'discount_rate', 'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2', 'original_amount' => 'decimal:2',
            'discount_rate' => 'decimal:4', 'discount_amount' => 'decimal:2',
            'opening_credit_amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_bill_id', 'booking_bill_id');
    }

    public function outstandingAmount(): float
    {
        $received = (float) $this->payments()->where('status', 'paid')->sum('amount');

        return round(max(0, (float) $this->total_amount - (float) $this->opening_credit_amount - $received), 2);
    }
}
