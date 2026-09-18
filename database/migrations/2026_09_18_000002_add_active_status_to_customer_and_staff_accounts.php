<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index()->after('remember_token');
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
