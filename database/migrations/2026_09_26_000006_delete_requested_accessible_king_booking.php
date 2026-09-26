<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')
            || ! Schema::hasTable('customers')
            || ! Schema::hasTable('rooms')) {
            return;
        }

        DB::transaction(function (): void {
            $bookingIds = DB::table('bookings')
                ->join('customers', 'customers.customer_id', '=', 'bookings.customer_id')
                ->join('rooms', 'rooms.room_id', '=', 'bookings.room_id')
                ->whereRaw('LOWER(customers.email) = ?', ['jerichohermogen52@gmail.com'])
                ->where('rooms.name', 'Accessible King')
                ->whereDate('bookings.check_in', '2026-09-26')
                ->whereDate('bookings.check_out', '2026-09-27')
                ->pluck('bookings.booking_id');

            if ($bookingIds->isEmpty()) {
                return;
            }

            $paymentIds = Schema::hasTable('payments')
                ? DB::table('payments')->whereIn('booking_id', $bookingIds)->pluck('payment_id')
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
                foreach ($bookingIds as $bookingId) {
                    DB::table('notifications')
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.booking_id')) = ?", [(string) $bookingId])
                        ->delete();
                }
            }

            DB::table('bookings')
                ->whereIn('booking_id', $bookingIds)
                ->delete();
        });
    }

    public function down(): void
    {
        // The user explicitly requested permanent removal of this booking.
    }
};
