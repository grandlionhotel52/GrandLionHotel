<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('payments')) {
            return;
        }

        DB::table('bookings')
            ->where('status', 'pending')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('payments')
                    ->whereColumn('payments.booking_id', 'bookings.booking_id')
                    ->where(function ($paymentQuery): void {
                        $paymentQuery->whereNull('payments.method')
                            ->orWhere('payments.method', '')
                            ->orWhere('payments.method', 'pending');
                    });
            })
            ->update(['status' => 'draft']);
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            DB::table('bookings')
                ->where('status', 'draft')
                ->update(['status' => 'pending']);
        }
    }
};
