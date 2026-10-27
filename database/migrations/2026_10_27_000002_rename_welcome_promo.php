<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('promo_codes')
            ->where('code', 'WELCOME10')
            ->update([
                'code' => 'NEWUSERDISCOUNT',
                'description' => 'New User Discount for your first hotel booking.',
                'discount_percent' => 30,
            ]);
    }

    public function down(): void
    {
        DB::table('promo_codes')
            ->where('code', 'NEWUSERDISCOUNT')
            ->update([
                'code' => 'WELCOME10',
                'description' => 'Welcome offer for your first hotel booking.',
                'discount_percent' => 30,
            ]);
    }
};
