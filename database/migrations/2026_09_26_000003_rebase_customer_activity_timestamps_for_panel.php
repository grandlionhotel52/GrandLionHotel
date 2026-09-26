<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PRESENTATION_DATES = [
        '2026-09-14',
        '2026-09-15',
        '2026-09-16',
        '2026-09-17',
        '2026-09-18',
        '2026-09-20',
        '2026-09-21',
        '2026-09-22',
        '2026-09-23',
        '2026-09-24',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasTable('bookings')) {
            return;
        }

        DB::transaction(function (): void {
            $customerDates = [];
            $customers = DB::table('customers')
                ->orderBy('customer_id')
                ->get(['customer_id']);

            foreach ($customers as $index => $customer) {
                $createdAt = $this->distributedTimestamp($index, $customers->count(), 9)
                    ->addMinutes($index * 7);

                DB::table('customers')
                    ->where('customer_id', $customer->customer_id)
                    ->update([
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt->copy()->addMinutes(30),
                    ]);

                $customerDates[(int) $customer->customer_id] = $createdAt;
            }

            $bookings = DB::table('bookings')
                ->orderBy('booking_id')
                ->get(['booking_id', 'customer_id']);

            foreach ($bookings as $index => $booking) {
                $createdAt = $this->distributedTimestamp($index, $bookings->count(), 13)
                    ->addMinutes($index * 11);
                $customerCreatedAt = $booking->customer_id
                    ? ($customerDates[(int) $booking->customer_id] ?? null)
                    : null;

                if ($customerCreatedAt && $createdAt->lessThan($customerCreatedAt)) {
                    $createdAt = $customerCreatedAt->copy()->setTime(13, 0)->addMinutes($index * 11);
                }

                $updatedAt = $createdAt->copy()->addHours(2);

                DB::table('bookings')
                    ->where('booking_id', $booking->booking_id)
                    ->update([
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]);

                foreach (['booking_guest_details', 'payments', 'booking_extra_bedding_requests'] as $table) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }

                    DB::table($table)
                        ->where('booking_id', $booking->booking_id)
                        ->update([
                            'created_at' => $createdAt,
                            'updated_at' => $updatedAt,
                        ]);
                }
            }
        });
    }

    public function down(): void
    {
        // This migration intentionally preserves the corrected presentation dates.
    }

    private function distributedTimestamp(int $index, int $total, int $hour): Carbon
    {
        $lastIndex = count(self::PRESENTATION_DATES) - 1;
        $dateIndex = $total <= 1
            ? 0
            : (int) round(($index * $lastIndex) / ($total - 1));

        return Carbon::parse(self::PRESENTATION_DATES[$dateIndex])->setTime($hour, 0);
    }
};
