<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')
            ->whereIn('status', ['refund_pending', 'refunded'])
            ->update(['status' => 'paid']);

        Schema::dropIfExists('refund_requests');
    }

    public function down(): void
    {
        // The removed feature and its data are intentionally not restored.
    }
};
