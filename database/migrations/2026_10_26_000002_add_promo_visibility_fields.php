<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table): void {
            $table->string('description', 255)->nullable()->after('code');
            $table->boolean('new_customers_only')->default(false)->index()->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table): void {
            $table->dropIndex(['new_customers_only']);
            $table->dropColumn(['description', 'new_customers_only']);
        });
    }
};
