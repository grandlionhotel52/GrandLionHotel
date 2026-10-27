<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('promo_codes')
            ->where('code', 'WELCOME10')
            ->update(['discount_percent' => 30]);
    }

    public function down(): void
    {
        DB::table('promo_codes')
            ->where('code', 'WELCOME10')
            ->update(['discount_percent' => 10]);
    }
};
