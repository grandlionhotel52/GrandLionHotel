<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')
            ->where('source', 'paymongo_checkout')
            ->whereNotNull('paid_at')
            ->whereNull('verified_at')
            ->update(['verified_at' => DB::raw('paid_at')]);
    }

    public function down(): void
    {
        // Verification timestamps are audit data and should not be erased on rollback.
    }
};
