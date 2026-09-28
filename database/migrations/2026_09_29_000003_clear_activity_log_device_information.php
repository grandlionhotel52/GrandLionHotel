<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs') || ! Schema::hasColumn('activity_logs', 'user_agent')) {
            return;
        }

        DB::table('activity_logs')
            ->whereNotNull('user_agent')
            ->update(['user_agent' => null]);
    }

    public function down(): void
    {
        // Previously captured device information cannot and should not be reconstructed.
    }
};
