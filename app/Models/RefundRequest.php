<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class RefundRequest extends Model
{
    use HasFactory;
    use HasLegacyIdAttribute;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_REJECTED = 'rejected';

    protected $primaryKey = 'refund_request_id';

    protected $fillable = [
        'payment_id',
        'reason',
        'status',
        'amount',
        'refund_method',
        'transaction_reference',
        'provider_refund_id',
        'provider_refund_status',
        'rejection_reason',
        'handled_by_admin_id',
        'notes',
        'requested_at',
        'approved_at',
        'processed_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function booking(): HasOneThrough
    {
        return $this->hasOneThrough(
            Booking::class,
            Payment::class,
            'payment_id',
            'booking_id',
            'payment_id',
            'booking_id'
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id', 'payment_id');
    }

    public function handledByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'handled_by_admin_id', 'admin_id');
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, match ($this->status) {
            self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => [self::STATUS_PROCESSED],
            default => [],
        }, true);
    }
}
