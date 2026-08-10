<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('payment_due_at')->nullable()->after('expired_at')->index();
        });

        DB::table('bookings')
            ->where('status', 'confirmed')
            ->whereNull('payment_due_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('payments')
                    ->whereColumn('payments.booking_id', 'bookings.booking_id')
                    ->whereIn('payments.status', ['paid', 'pending_verification', 'refund_pending']);
            })
            ->update(['payment_due_at' => now()->addHours(24)]);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn('payment_due_at');
        });
    }
};
