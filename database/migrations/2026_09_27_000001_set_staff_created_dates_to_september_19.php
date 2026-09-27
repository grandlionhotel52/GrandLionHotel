<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        $staffMembers = DB::table('staff')
            ->orderBy('staff_id')
            ->get(['staff_id']);

        foreach ($staffMembers as $index => $staff) {
            DB::table('staff')
                ->where('staff_id', $staff->staff_id)
                ->update([
                    'created_at' => Carbon::parse('2026-09-19 09:00:00')->addMinutes($index * 13),
                ]);
        }
    }

    public function down(): void
    {
        // The prior creation timestamps cannot be reconstructed safely.
    }
};
