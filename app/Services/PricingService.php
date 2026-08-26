<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RoomDateDiscount;
use App\Models\Room;
use Carbon\Carbon;
use InvalidArgumentException;

class PricingService
{
    public function quoteStay(
        Room $room,
        string $checkIn,
        string $checkOut,
        ?int $guests = null,
        bool $includeServiceFee = false
    ): array
    {
        $start = Carbon::parse($checkIn)->startOfDay();
        $end = Carbon::parse($checkOut)->startOfDay();
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Check-out must be at least one day after check-in.');
        }

        $discountRanges = RoomDateDiscount::query()
            ->where('room_id', (int) $room->id)
            ->whereDate('discount_date_start', '<=', $end->copy()->subDay()->toDateString())
            ->whereDate('discount_date_end', '>=', $start->toDateString())
            ->get(['discount_date_start', 'discount_date_end', 'discount_percent']);

        $nightlyRate = round((float) $room->price_per_night, 2);
        $nights = $start->diffInDays($end);
        $guestCount = max(1, (int) ($guests ?? Room::standardGuestCapacity()));
        $extraBeddingCount = $this->calculateExtraBeddingCount($guestCount);
        $extraBeddingFeePerNight = $this->extraBeddingFeePerNight();
        $baseTotal = round($nightlyRate * $nights, 2);
        $total = 0.0;
        $discountAmount = 0.0;
        $discountedNights = 0;
        $breakdown = [];
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            $dateKey = $cursor->toDateString();
            $discountPercent = (float) ($discountRanges
                ->first(static fn (RoomDateDiscount $discount): bool => $cursor->betweenIncluded(
                    $discount->discount_date_start,
                    $discount->discount_date_end
                ))
                ?->discount_percent ?? 0);
            $discountPercent = max(0, min(100, $discountPercent));
            $multiplier = 1 - ($discountPercent / 100);
            $discountedRate = round($nightlyRate * $multiplier, 2);

            if ($discountPercent > 0) {
                $discountedNights++;
            }

            $total += $discountedRate;
            $discountAmount += max(0, $nightlyRate - $discountedRate);
            $breakdown[] = [
                'date' => $dateKey,
                'base_rate' => $nightlyRate,
                'discount_percent' => round($discountPercent, 2),
                'discounted_rate' => $discountedRate,
            ];

            $cursor->addDay();
        }

        $roomTotal = round($total, 2);
        $discountAmount = round($discountAmount, 2);
        $extraBeddingTotal = round($extraBeddingCount * $extraBeddingFeePerNight * $nights, 2);
        $chargeableSubtotal = round($roomTotal + $extraBeddingTotal, 2);
        $charges = $this->statutoryCharges($chargeableSubtotal, $includeServiceFee);
        $grandTotal = $charges['total'];

        return [
            'check_in' => $start->toDateString(),
            'check_out' => $end->toDateString(),
            'nights' => $nights,
            'guests' => $guestCount,
            'standard_guests' => Room::standardGuestCapacity(),
            'base_nightly_rate' => $nightlyRate,
            'average_nightly_rate' => $nights > 0 ? round($chargeableSubtotal / $nights, 2) : $nightlyRate,
            'base_total' => $baseTotal,
            'room_total' => $roomTotal,
            'extra_bedding_count' => $extraBeddingCount,
            'extra_bedding_fee_per_night' => $extraBeddingFeePerNight,
            'extra_bedding_total' => $extraBeddingTotal,
            'chargeable_subtotal' => $chargeableSubtotal,
            'service_fee_rate' => $charges['service_fee_rate'],
            'service_fee' => $charges['service_fee'],
            'service_fee_applies' => $includeServiceFee,
            'local_tax_rate' => $charges['local_tax_rate'],
            'local_tax' => $charges['local_tax'],
            'vat_rate' => $charges['vat_rate'],
            'vat' => $charges['vat'],
            'total' => $grandTotal,
            'discount_amount' => $discountAmount,
            'discounted_nights' => $discountedNights,
            'has_date_discount' => $discountedNights > 0,
            'nightly_breakdown' => $breakdown,
        ];
    }

    public function calculateTotal(
        Room $room,
        string $checkIn,
        string $checkOut,
        ?int $guests = null,
        bool $includeServiceFee = false
    ): float
    {
        return $this->quoteStay($room, $checkIn, $checkOut, $guests, $includeServiceFee)['total'];
    }

    public function quoteBooking(Booking $booking): array
    {
        $room = $booking->getRelationValue('room');

        if (!$room && !is_null($booking->room_id)) {
            $room = $booking->room()->first(['room_id', 'price_per_night']);
            if ($room) {
                $booking->setRelation('room', $room);
            }
        }

        if (!$room || !$booking->check_in || !$booking->check_out) {
            return [
                'check_in' => null,
                'check_out' => null,
                'nights' => 0,
                'guests' => max(1, (int) $booking->guests),
                'standard_guests' => Room::standardGuestCapacity(),
                'base_nightly_rate' => 0.0,
                'average_nightly_rate' => 0.0,
                'base_total' => 0.0,
                'room_total' => 0.0,
                'extra_bedding_count' => $this->calculateExtraBeddingCount($booking->guests),
                'extra_bedding_fee_per_night' => $this->extraBeddingFeePerNight(),
                'extra_bedding_total' => 0.0,
                'chargeable_subtotal' => 0.0,
                'service_fee_rate' => $this->serviceFeeRate(),
                'service_fee' => 0.0,
                'service_fee_applies' => false,
                'local_tax_rate' => $this->localTaxRate(),
                'local_tax' => 0.0,
                'vat_rate' => $this->vatRate(),
                'vat' => 0.0,
                'total' => 0.0,
                'discount_amount' => 0.0,
                'discounted_nights' => 0,
                'has_date_discount' => false,
                'nightly_breakdown' => [],
            ];
        }

        $mealPlan = (string) ($booking->reservation_meta['meal_plan'] ?? 'room_only');

        return $this->quoteStay(
            $room,
            $booking->check_in->toDateString(),
            $booking->check_out->toDateString(),
            $booking->guests,
            $mealPlan === 'breakfast_included'
        );
    }

    public function calculateExtraBeddingCount(?int $guests): int
    {
        return max(0, max(1, (int) $guests) - Room::standardGuestCapacity());
    }

    public function extraBeddingFeePerNight(): float
    {
        return round(max(0, (float) config('pricing.extra_bedding_fee_per_night', 0)), 2);
    }

    public function statutoryCharges(float $subtotal, bool $includeServiceFee = false): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $serviceFeeRate = $this->serviceFeeRate();
        $localTaxRate = $this->localTaxRate();
        $vatRate = $this->vatRate();
        $serviceFee = $includeServiceFee ? round($subtotal * $serviceFeeRate, 2) : 0.0;
        $localTax = round($subtotal * $localTaxRate, 2);
        $vat = round($subtotal * $vatRate, 2);

        return [
            'subtotal' => $subtotal,
            'service_fee_rate' => $serviceFeeRate,
            'service_fee' => $serviceFee,
            'local_tax_rate' => $localTaxRate,
            'local_tax' => $localTax,
            'vat_rate' => $vatRate,
            'vat' => $vat,
            'total' => round($subtotal + $serviceFee + $localTax + $vat, 2),
        ];
    }

    public function serviceFeeRate(): float
    {
        return max(0, (float) config('pricing.service_fee_rate', 0.08));
    }

    public function localTaxRate(): float
    {
        return max(0, (float) config('pricing.local_tax_rate', 0.05));
    }

    public function vatRate(): float
    {
        return max(0, (float) config('pricing.vat_rate', 0.12));
    }
}
