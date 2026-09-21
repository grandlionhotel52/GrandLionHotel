<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('reschedule_approved_by_admin_id')
                ->nullable()
                ->after('reschedule_requested_at')
                ->constrained('admins', 'admin_id')
                ->nullOnDelete();
            $table->timestamp('reschedule_approved_at')
                ->nullable()
                ->after('reschedule_approved_by_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reschedule_approved_by_admin_id');
            $table->dropColumn('reschedule_approved_at');
        });
    }
};
