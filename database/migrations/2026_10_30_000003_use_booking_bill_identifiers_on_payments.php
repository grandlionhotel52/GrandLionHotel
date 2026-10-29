<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_bills_rekeyed', function (Blueprint $table): void {
            $table->bigIncrements('booking_bill_id');
            $table->unsignedBigInteger('booking_id')->unique();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('original_amount', 12, 2)->nullable();
            $table->decimal('discount_rate', 8, 4)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('opening_credit_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->cascadeOnDelete();
        });
        DB::table('booking_bills')->orderBy('booking_id')->chunkById(200, function ($bills): void {
            foreach ($bills as $bill) {
                // Preserve audit subject IDs issued while the bill used a shared key.
                DB::table('booking_bills_rekeyed')->insert(['booking_bill_id' => $bill->booking_id] + (array) $bill);
            }
        }, 'booking_id');
        Schema::table('payments', fn (Blueprint $table) => $table->unsignedBigInteger('booking_bill_id')->nullable());
        DB::table('payments')->update(['booking_bill_id' => DB::raw('booking_id')]);
        foreach (Schema::getForeignKeys('payments') as $foreign) {
            if ($foreign['columns'] === ['booking_id']) {
                Schema::table('payments', fn (Blueprint $table) => $table->dropForeign($foreign['name'] ?? $foreign['columns']));
            }
        }
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_booking_id_status_index');
            $table->dropColumn('booking_id');
            $table->unsignedBigInteger('booking_bill_id')->nullable(false)->change();
            $table->foreign('booking_bill_id')->references('booking_bill_id')->on('booking_bills_rekeyed')->cascadeOnDelete();
            $table->index(['booking_bill_id', 'status']);
        });
        Schema::drop('booking_bills');
        Schema::rename('booking_bills_rekeyed', 'booking_bills');
    }

    public function down(): void
    {
        throw new RuntimeException('Restore a database backup to revert bill identifiers.');
    }
};
