<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        DB::transaction(function (): void {
            $query = DB::table('bookings');
            $paymentStatus = 'payments.status as payment_status';
            if (Schema::hasColumn('payments', 'booking_id')) {
                $query->leftJoin('payments', 'payments.booking_id', '=', 'bookings.booking_id');
            } else {
                $totals = DB::table('payments')->select('booking_bill_id')
                    ->selectRaw("SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as received")
                    ->groupBy('booking_bill_id');
                $query->leftJoin('booking_bills', 'booking_bills.booking_id', '=', 'bookings.booking_id')
                    ->leftJoinSub($totals, 'collections', 'collections.booking_bill_id', '=', 'booking_bills.booking_bill_id');
                $paymentStatus = DB::raw("CASE WHEN booking_bills.total_amount - booking_bills.opening_credit_amount <= COALESCE(collections.received, 0) THEN 'paid' ELSE 'unpaid' END as payment_status");
            }
            $bookings = $query
                ->whereNotNull('bookings.customer_id')
                ->whereIn('bookings.status', ['pending', 'confirmed'])
                ->get([
                    'bookings.booking_id',
                    'bookings.customer_id',
                    'bookings.room_id',
                    'bookings.check_in',
                    'bookings.check_out',
                    'bookings.status',
                    'bookings.actual_check_in_at',
                    $paymentStatus,
                ]);

            $duplicateIds = $bookings
                ->groupBy(fn (object $booking): string => implode('|', [
                    $booking->customer_id,
                    $booking->room_id,
                    $booking->check_in,
                    $booking->check_out,
                ]))
                ->filter(fn (Collection $group): bool => $group->count() > 1)
                ->flatMap(function (Collection $group): Collection {
                    $ordered = $group->sort(function (object $left, object $right): int {
                        $scoreComparison = $this->bookingPriority($right) <=> $this->bookingPriority($left);

                        return $scoreComparison !== 0
                            ? $scoreComparison
                            : ((int) $left->booking_id <=> (int) $right->booking_id);
                    })->values();

                    return $ordered->slice(1)->pluck('booking_id');
                })
                ->values();

            $this->deleteBookings($duplicateIds);
        });
    }

    public function down(): void
    {
        // Exact duplicate bookings cannot be restored safely.
    }

    private function bookingPriority(object $booking): int
    {
        return ($booking->actual_check_in_at !== null ? 4 : 0)
            + ($booking->payment_status === 'paid' ? 2 : 0)
            + ($booking->status === 'confirmed' ? 1 : 0);
    }

    private function deleteBookings(Collection $bookingIds): void
    {
        if ($bookingIds->isEmpty()) {
            return;
        }

        $paymentIds = Schema::hasTable('payments')
            ? DB::table('payments')->when(
                Schema::hasColumn('payments', 'booking_id'),
                fn ($query) => $query->whereIn('booking_id', $bookingIds),
                fn ($query) => $query->whereIn('booking_bill_id', DB::table('booking_bills')->whereIn('booking_id', $bookingIds)->select('booking_bill_id'))
            )->pluck('payment_id')
            : collect();

        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')
                ->where('subject_type', 'Booking')
                ->whereIn('subject_id', $bookingIds)
                ->delete();

            if ($paymentIds->isNotEmpty()) {
                DB::table('activity_logs')
                    ->where('subject_type', 'Payment')
                    ->whereIn('subject_id', $paymentIds)
                    ->delete();
            }
        }

        if (Schema::hasTable('notifications')) {
            $bookingIdLookup = array_fill_keys(array_map('strval', $bookingIds->all()), true);
            $notificationIds = DB::table('notifications')
                ->get(['id', 'data'])
                ->filter(function (object $notification) use ($bookingIdLookup): bool {
                    $data = json_decode((string) $notification->data, true);

                    return is_array($data)
                        && isset($bookingIdLookup[(string) ($data['booking_id'] ?? '')]);
                })
                ->pluck('id');

            if ($notificationIds->isNotEmpty()) {
                DB::table('notifications')->whereIn('id', $notificationIds)->delete();
            }
        }

        DB::table('bookings')->whereIn('booking_id', $bookingIds)->delete();
    }
};
