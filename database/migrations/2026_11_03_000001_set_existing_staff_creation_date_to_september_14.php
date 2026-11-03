<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('staff')->whereNotNull('created_at')
                ->chunkById(200, function ($staffMembers): void {
                    foreach ($staffMembers as $staff) {
                        $createdAt = Carbon::parse($staff->created_at);

                        DB::table('staff')->where('staff_id', $staff->staff_id)->update([
                            'created_at' => $createdAt->setDate($createdAt->year, 9, 14)->toDateTimeString(),
                        ]);
                    }
                }, 'staff_id');
        });
    }

    public function down(): void
    {
        // The original creation dates cannot be inferred after this correction.
    }
};
