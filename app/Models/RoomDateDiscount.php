<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomDateDiscount extends Model
{
    use HasLegacyIdAttribute;

    protected $primaryKey = 'room_date_discount_id';

    protected $fillable = [
        'room_id',
        'discount_date',
        'discount_date_start',
        'discount_date_end',
        'discount_percent',
    ];

    protected function casts(): array
    {
        return [
            'discount_date_start' => 'date',
            'discount_date_end' => 'date',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function setDiscountDateAttribute(mixed $value): void
    {
        $this->attributes['discount_date_start'] = $value;
        $this->attributes['discount_date_end'] = $value;
    }

    public function getDiscountDateAttribute(): mixed
    {
        return $this->discount_date_start;
    }
}
