<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingExtraBeddingRequest extends Model
{
    protected $primaryKey = 'booking_extra_bedding_request_id';

    protected $fillable = [
        'booking_id',
        'requested_count',
        'approved_count',
        'customer_message',
        'status',
        'staff_response',
        'responded_by_staff_id',
        'requested_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_count' => 'integer',
            'approved_count' => 'integer',
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

    public function respondedByStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responded_by_staff_id', 'staff_id');
    }
}
