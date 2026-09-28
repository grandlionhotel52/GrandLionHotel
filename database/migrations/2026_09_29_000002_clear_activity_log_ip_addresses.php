<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs') || ! Schema::hasColumn('activity_logs', 'ip_address')) {
            return;
        }

        DB::table('activity_logs')
            ->whereNotNull('ip_address')
            ->update(['ip_address' => null]);
    }

    public function down(): void
    {
        // Previously captured IP addresses cannot and should not be reconstructed.
    }
};
