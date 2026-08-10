<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table): void {
            $table->decimal('amount', 10, 2)->nullable()->after('status');
            $table->string('refund_method', 40)->nullable()->after('amount');
            $table->string('transaction_reference', 120)->nullable()->after('refund_method');
            $table->text('rejection_reason')->nullable()->after('transaction_reference');
            $table->foreignId('handled_by_admin_id')->nullable()->after('rejection_reason')
                ->constrained('admins', 'admin_id')
                ->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('processed_at');
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table): void {
            $table->dropIndex(['status', 'requested_at']);
            $table->dropConstrainedForeignId('handled_by_admin_id');
            $table->dropColumn([
                'amount',
                'refund_method',
                'transaction_reference',
                'rejection_reason',
                'rejected_at',
            ]);
        });
    }
};
