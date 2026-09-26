<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PANEL_DATES = [
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
        DB::transaction(function (): void {
            $customerDates = $this->rebaseCustomers();
            $this->rebaseBookings($customerDates);
            $this->rebaseActivityLogs();
            $this->rebaseNotifications();
        });
    }

    public function down(): void
    {
        // Presentation timestamp changes are intentionally retained.
    }

    /** @return array<int, Carbon> */
    private function rebaseCustomers(): array
    {
        if (! Schema::hasTable('customers')) {
            return [];
        }

        $dates = [];
        $customers = DB::table('customers')
            ->orderBy('customer_id')
            ->get(['customer_id']);

        foreach ($customers as $index => $customer) {
            $createdAt = $this->distributedTimestamp($index, $customers->count(), 9)
                ->addMinutes($index % 45);

            DB::table('customers')
                ->where('customer_id', $customer->customer_id)
                ->update([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addMinutes(30),
                ]);

            $dates[(int) $customer->customer_id] = $createdAt;
        }

        return $dates;
    }

    /** @param array<int, Carbon> $customerDates */
    private function rebaseBookings(array $customerDates): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        $bookings = DB::table('bookings')
            ->orderBy('booking_id')
            ->get(['booking_id', 'customer_id']);

        foreach ($bookings as $index => $booking) {
            $createdAt = $this->distributedTimestamp($index, $bookings->count(), 13)
                ->addMinutes($index % 45);
            $customerCreatedAt = $booking->customer_id
                ? ($customerDates[(int) $booking->customer_id] ?? null)
                : null;

            if ($customerCreatedAt && $createdAt->lessThan($customerCreatedAt)) {
                $createdAt = $customerCreatedAt->copy()->setTime(13, 0)->addMinutes($index % 45);
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
    }

    private function rebaseActivityLogs(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        $logs = DB::table('activity_logs')
            ->orderBy('activity_log_id')
            ->get(['activity_log_id']);

        foreach ($logs as $index => $log) {
            $occurredAt = $this->distributedTimestamp($index, $logs->count(), 8)
                ->addMinutes($index % 600);

            DB::table('activity_logs')
                ->where('activity_log_id', $log->activity_log_id)
                ->update([
                    'created_at' => $occurredAt,
                    'updated_at' => $occurredAt,
                ]);
        }
    }

    private function rebaseNotifications(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $notifications = DB::table('notifications')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id']);

        foreach ($notifications as $index => $notification) {
            $createdAt = $this->distributedTimestamp($index, $notifications->count(), 14)
                ->addMinutes($index % 300);

            DB::table('notifications')
                ->where('id', $notification->id)
                ->update([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
        }
    }

    private function distributedTimestamp(int $index, int $total, int $hour): Carbon
    {
        $lastIndex = count(self::PANEL_DATES) - 1;
        $dateIndex = $total <= 1
            ? 0
            : (int) round(($index * $lastIndex) / ($total - 1));

        return Carbon::parse(self::PANEL_DATES[$dateIndex])->setTime($hour, 0);
    }
};
