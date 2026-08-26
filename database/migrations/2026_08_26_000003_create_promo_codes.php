<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table): void {
            $table->id('promo_code_id');
            $table->string('code', 40)->unique();
            $table->decimal('discount_percent', 5, 2);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('booking_discounts', function (Blueprint $table): void {
            $table->unsignedBigInteger('promo_code_id')->nullable()->after('booking_id');
            $table->foreign('promo_code_id')->references('promo_code_id')->on('promo_codes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_discounts', function (Blueprint $table): void {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn('promo_code_id');
        });
        Schema::dropIfExists('promo_codes');
    }
};
