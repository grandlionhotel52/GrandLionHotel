<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_reviews', function (Blueprint $table): void {
            $table->unsignedBigInteger('room_id')->nullable()->after('booking_id');
            $table->unsignedBigInteger('customer_id')->nullable()->after('room_id');
        });

        $reviews = DB::table('room_reviews')
            ->join('bookings', 'bookings.booking_id', '=', 'room_reviews.booking_id')
            ->get([
                'room_reviews.room_review_id',
                'bookings.room_id',
                'bookings.customer_id',
            ]);

        foreach ($reviews as $review) {
            DB::table('room_reviews')
                ->where('room_review_id', $review->room_review_id)
                ->update([
                    'room_id' => $review->room_id,
                    'customer_id' => $review->customer_id,
                ]);
        }

        // Keep the newest review if historical bookings produced more than one
        // review by the same customer for the same room.
        $seen = [];
        $duplicates = [];
        $existingReviews = DB::table('room_reviews')
            ->orderByDesc('updated_at')
            ->orderByDesc('room_review_id')
            ->get(['room_review_id', 'room_id', 'customer_id']);

        foreach ($existingReviews as $review) {
            $key = $review->room_id.':'.$review->customer_id;

            if (isset($seen[$key])) {
                $duplicates[] = $review->room_review_id;
            } else {
                $seen[$key] = true;
            }
        }

        if ($duplicates !== []) {
            DB::table('room_reviews')->whereIn('room_review_id', $duplicates)->delete();
        }

        Schema::table('room_reviews', function (Blueprint $table): void {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
            $table->unique(['room_id', 'customer_id']);
            $table->foreign('room_id')->references('room_id')->on('rooms')->cascadeOnDelete();
            $table->foreign('customer_id')->references('customer_id')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('room_reviews')->whereNull('booking_id')->delete();

        Schema::table('room_reviews', function (Blueprint $table): void {
            $table->dropForeign(['room_id']);
            $table->dropForeign(['customer_id']);
            $table->dropUnique(['room_id', 'customer_id']);
            $table->dropColumn(['room_id', 'customer_id']);
            $table->unsignedBigInteger('booking_id')->nullable(false)->change();
        });
    }
};
