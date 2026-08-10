<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->timestamp('expired_at')->nullable()->after('status');
            $table->timestamp('check_in_reminder_sent_at')->nullable()->after('expired_at');
            $table->index(['status', 'created_at']);
            $table->index(['status', 'check_in', 'check_in_reminder_sent_at'], 'bookings_check_in_reminder_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex('bookings_check_in_reminder_index');
            $table->dropColumn(['expired_at', 'check_in_reminder_sent_at']);
        });
    }
};
