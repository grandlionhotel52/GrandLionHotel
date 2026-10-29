<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDetail extends Model
{
    use HasFactory;
    use HasLegacyIdAttribute;

    protected $primaryKey = 'booking_detail_id';

    protected $fillable = [
        'booking_id',
        'adults',
        'kids',
        'meal_plan',
        'payment_preference',
    ];

    protected function casts(): array
    {
        return [
            'adults' => 'integer',
            'kids' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'booking_id');
    }

}
