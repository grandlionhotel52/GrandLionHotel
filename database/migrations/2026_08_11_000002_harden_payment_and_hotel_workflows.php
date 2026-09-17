<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paymongo_checkout_sessions', function (Blueprint $table): void {
            $table->id('paymongo_checkout_session_id');
            $table->foreignId('booking_id')->constrained('bookings', 'booking_id')->cascadeOnDelete();
            $table->string('provider_session_id', 120)->unique();
            $table->text('checkout_url');
            $table->unsignedBigInteger('amount_centavos');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('payment_reminder_sent_at')->nullable()->after('payment_due_at');
            $table->timestamp('no_show_at')->nullable()->after('actual_check_out_at');
            $table->string('cancellation_reason', 1000)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['payment_reminder_sent_at', 'no_show_at', 'cancellation_reason']);
        });
        Schema::dropIfExists('paymongo_checkout_sessions');
    }
};
