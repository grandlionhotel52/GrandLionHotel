<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_extra_bedding_requests', function (Blueprint $table): void {
            $table->bigIncrements('booking_extra_bedding_request_id');
            $table->unsignedBigInteger('booking_id')->unique();
            $table->unsignedTinyInteger('requested_count')->default(1);
            $table->unsignedTinyInteger('approved_count')->default(0);
            $table->text('customer_message');
            $table->string('status', 30)->default('pending');
            $table->text('staff_response')->nullable();
            $table->unsignedBigInteger('responded_by_staff_id')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')
                ->references('booking_id')
                ->on('bookings')
                ->cascadeOnDelete();
            $table->foreign('responded_by_staff_id')
                ->references('staff_id')
                ->on('staff')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_extra_bedding_requests');
    }
};
