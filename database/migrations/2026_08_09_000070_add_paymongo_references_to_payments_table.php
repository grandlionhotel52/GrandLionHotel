<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('provider_session_id')->nullable()->unique()->after('transaction_reference');
            $table->string('provider_payment_id')->nullable()->unique()->after('provider_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['provider_payment_id']);
            $table->dropUnique(['provider_session_id']);
            $table->dropColumn(['provider_session_id', 'provider_payment_id']);
        });
    }
};
