<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_verifications', function (Blueprint $table): void {
            $table->string('address_line')->nullable()->after('phone');
            $table->string('city', 120)->nullable()->after('address_line');
            $table->string('province', 120)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('registration_verifications', function (Blueprint $table): void {
            $table->dropColumn(['address_line', 'city', 'province']);
        });
    }
};
