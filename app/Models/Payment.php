<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    use HasEncryptedRouteKey;
    use HasLegacyIdAttribute;

    public const METHOD_CASH = 'cash';
    public const METHOD_INSTAPAY = 'instapay';
    public const METHOD_CREDIT_DEBIT_CARD = 'credit_debit_card';
    public const METHOD_GCASH = 'gcash';
    public const METHOD_PAYMAYA = 'paymaya';
    public const METHOD_QRPH = 'qrph';

    public const ONLINE_METHODS = [
        self::METHOD_INSTAPAY,
        self::METHOD_CREDIT_DEBIT_CARD,
        self::METHOD_GCASH,
        self::METHOD_PAYMAYA,
        self::METHOD_QRPH,
    ];

    protected $primaryKey = 'payment_id';

    // Legacy callers may supply snapshot fields; their storage belongs to the bill.
    private array $billAttributes = [];

    private ?int $legacyBookingId = null;

    protected $fillable = [
        'booking_id',
        'booking_bill_id',
        'amount',
        'method',
        'status',
        'source',
        'qr_reference',
        'customer_reference',
        'payment_proof_path',
        'original_amount',
        'discount_rate',
        'discount_amount',
        'transaction_reference',
        'provider_session_id',
        'provider_payment_id',
        'paid_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'original_amount' => 'decimal:2',
            'discount_rate' => 'decimal:4',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function booking(): HasOneThrough
    {
        return $this->hasOneThrough(Booking::class, BookingBill::class,
            'booking_bill_id', 'booking_id', 'booking_bill_id', 'booking_id');
    }

    public function getBookingIdAttribute($value): mixed
    {
        return $value ?? $this->legacyBookingId ?? $this->bill?->booking_id;
    }

    public function setBookingIdAttribute($value): void
    {
        $this->legacyBookingId = $value === null ? null : (int) $value;
    }

    public static function allowedMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_INSTAPAY,
            self::METHOD_CREDIT_DEBIT_CARD,
            self::METHOD_GCASH,
            self::METHOD_PAYMAYA,
            self::METHOD_QRPH,
        ];
    }

    public static function isOnlineMethod(?string $method): bool
    {
        return in_array(strtolower(trim((string) $method)), self::ONLINE_METHODS, true);
    }

    public static function methodLabel(?string $method): string
    {
        $normalized = strtolower(trim((string) $method));

        return match ($normalized) {
            self::METHOD_CASH => 'Cash',
            self::METHOD_INSTAPAY, 'bank_transfer' => 'InstaPay',
            self::METHOD_CREDIT_DEBIT_CARD => 'Credit/Debit Card',
            self::METHOD_GCASH => 'GCash via PayMongo',
            self::METHOD_QRPH => 'QR Ph via PayMongo',
            self::METHOD_PAYMAYA => 'Maya',
            default => ucfirst(str_replace('_', ' ', $normalized !== '' ? $normalized : 'n/a')),
        };
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(BookingBill::class, 'booking_bill_id', 'booking_bill_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            $bill = $payment->booking_bill_id
                ? BookingBill::query()->findOrFail($payment->booking_bill_id)
                : BookingBill::firstOrCreate(
                    ['booking_id' => $payment->legacyBookingId],
                    ['total_amount' => $payment->amount]
                );
            $payment->booking_bill_id = $bill->getKey();
            if ($payment->billAttributes !== []) {
                $bill->update($payment->billAttributes);
                $payment->billAttributes = [];
            }
            $payment->unsetRelation('bill');
        });
    }

    public function outstandingAmount(): float
    {
        return $this->bill?->outstandingAmount() ?? 0.0;
    }

    public function getBalanceDueAttribute(): string
    {
        return number_format($this->outstandingAmount(), 2, '.', '');
    }

    public function getOriginalAmountAttribute($value): mixed
    {
        return $value ?? $this->bill?->original_amount;
    }

    public function setOriginalAmountAttribute($value): void
    {
        $this->billAttributes['original_amount'] = $value;
    }

    public function getDiscountRateAttribute($value): mixed
    {
        return $value ?? $this->bill?->discount_rate;
    }

    public function setDiscountRateAttribute($value): void
    {
        $this->billAttributes['discount_rate'] = $value;
    }

    public function getDiscountAmountAttribute($value): mixed
    {
        return $value ?? $this->bill?->discount_amount;
    }

    public function setDiscountAmountAttribute($value): void
    {
        $this->billAttributes['discount_amount'] = $value;
    }

    public function ensureTransactionReference(?int $bookingId = null): string
    {
        $existingReference = strtoupper(trim((string) $this->transaction_reference));
        if ($existingReference !== '') {
            if ($existingReference !== $this->transaction_reference) {
                $this->forceFill(['transaction_reference' => $existingReference])->save();
            }

            return $existingReference;
        }

        $resolvedBookingId = max(1, (int) ($bookingId ?? $this->booking_id));

        do {
            $candidate = self::generateTransactionReference($resolvedBookingId);

            $query = self::query()->where('transaction_reference', $candidate);
            if ($this->exists) {
                $query->where($this->getKeyName(), '!=', $this->getKey());
            }
        } while ($query->exists());

        $this->forceFill(['transaction_reference' => $candidate])->save();

        return $candidate;
    }

    public static function generateTransactionReference(int $bookingId): string
    {
        $datePart = now()->format('Ymd');
        $bookingPart = str_pad((string) max(1, $bookingId), 6, '0', STR_PAD_LEFT);
        $randomPart = Str::upper(Str::random(6));

        return "GLH-{$datePart}-B{$bookingPart}-{$randomPart}";
    }

}
