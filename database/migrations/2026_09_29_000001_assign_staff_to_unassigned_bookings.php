<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('staff')) {
            return;
        }

        $staffMembers = DB::table('staff')
            ->when(
                Schema::hasColumn('staff', 'is_active'),
                fn ($query) => $query->where('is_active', true)
            )
            ->orderBy('staff_id')
            ->get(['staff_id']);

        if ($staffMembers->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($staffMembers): void {
            $workloads = [];

            foreach ($staffMembers as $staff) {
                $workloads[(int) $staff->staff_id] = DB::table('bookings')
                    ->where('staff_id', $staff->staff_id)
                    ->count();
            }

            $adminId = Schema::hasTable('admins')
                ? DB::table('admins')->orderBy('admin_id')->value('admin_id')
                : null;

            $bookings = DB::table('bookings')
                ->whereNull('staff_id')
                ->orderBy('booking_id')
                ->get(['booking_id', 'created_at']);

            foreach ($bookings as $booking) {
                asort($workloads, SORT_NUMERIC);
                $staffId = (int) array_key_first($workloads);

                $updated = DB::table('bookings')
                    ->where('booking_id', $booking->booking_id)
                    ->whereNull('staff_id')
                    ->update(['staff_id' => $staffId]);

                if ($updated !== 1) {
                    continue;
                }

                $workloads[$staffId]++;

                if (! Schema::hasTable('activity_logs')) {
                    continue;
                }

                DB::table('activity_logs')->insert([
                    'actor_type' => $adminId ? 'Admin' : null,
                    'actor_id' => $adminId,
                    'action' => 'updated',
                    'subject_type' => 'Booking',
                    'subject_id' => $booking->booking_id,
                    'changes' => json_encode([
                        'before' => ['staff_id' => null],
                        'after' => ['staff_id' => $staffId],
                    ], JSON_THROW_ON_ERROR),
                    'ip_address' => null,
                    'user_agent' => null,
                    'created_at' => $booking->created_at,
                    'updated_at' => $booking->created_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Staff assignments may have been used operationally after deployment.
    }
};
