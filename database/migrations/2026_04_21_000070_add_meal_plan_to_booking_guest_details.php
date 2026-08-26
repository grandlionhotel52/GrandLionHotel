<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('booking_guest_details', 'meal_plan')) {
            Schema::table('booking_guest_details', function (Blueprint $table): void {
                $table->string('meal_plan', 30)->default('room_only')->after('kids');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('booking_guest_details', 'meal_plan')) {
            Schema::table('booking_guest_details', function (Blueprint $table): void {
                $table->dropColumn('meal_plan');
            });
        }
    }
};
