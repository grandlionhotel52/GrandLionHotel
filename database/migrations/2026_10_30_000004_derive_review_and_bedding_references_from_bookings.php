<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (DB::table('room_reviews')->orderBy('room_review_id')->get() as $review) {
                $booking = $review->booking_id
                    ? DB::table('bookings')->where('booking_id', $review->booking_id)->first()
                    : DB::table('bookings')->where('room_id', $review->room_id)->where('customer_id', $review->customer_id)
                        ->where('status', 'completed')
                        ->whereNotIn('booking_id', DB::table('room_reviews')->whereNotNull('booking_id')->select('booking_id'))
                        ->orderByDesc('check_out')->orderByDesc('booking_id')->first();
                if (! $booking) {
                    $this->archive('RoomReview', $review->room_review_id, 'unlinked_review_archived', (array) $review);
                    DB::table('room_reviews')->where('room_review_id', $review->room_review_id)->delete();
                    continue;
                }
                if (! $review->booking_id || $review->room_id != $booking->room_id || $review->customer_id != $booking->customer_id) {
                    $this->archive('RoomReview', $review->room_review_id, 'review_references_normalized', (array) $review);
                }
                DB::table('room_reviews')->where('room_review_id', $review->room_review_id)->update(['booking_id' => $booking->booking_id]);
            }
            foreach (DB::table('booking_extra_bedding_requests')->whereNotNull('responded_by_staff_id')->get() as $request) {
                $booking = DB::table('bookings')->where('booking_id', $request->booking_id)->first();
                if ($booking && ! $booking->staff_id) {
                    DB::table('bookings')->where('booking_id', $booking->booking_id)->update(['staff_id' => $request->responded_by_staff_id]);
                } elseif ($booking && $booking->staff_id != $request->responded_by_staff_id) {
                    $this->archive('BookingExtraBeddingRequest', $request->booking_extra_bedding_request_id, 'legacy_bedding_responder_archived', (array) $request);
                }
            }
        });
        $this->dropForeign('room_reviews', 'room_id');
        $this->dropForeign('room_reviews', 'customer_id');
        $this->dropForeign('booking_extra_bedding_requests', 'responded_by_staff_id');
        Schema::table('room_reviews', function (Blueprint $table): void {
            $table->dropUnique('room_reviews_room_id_customer_id_unique');
            $table->dropColumn(['room_id', 'customer_id']);
            $table->unsignedBigInteger('booking_id')->nullable(false)->change();
        });
        Schema::table('booking_extra_bedding_requests', fn (Blueprint $table) => $table->dropColumn('responded_by_staff_id'));
    }

    private function dropForeign(string $name, string $column): void
    {
        foreach (Schema::getForeignKeys($name) as $foreign) {
            if ($foreign['columns'] === [$column]) {
                Schema::table($name, fn (Blueprint $table) => $table->dropForeign($foreign['name'] ?? $foreign['columns']));
            }
        }
    }

    private function archive(string $type, int $id, string $action, array $before): void
    {
        DB::table('activity_logs')->insert([
            'actor_type' => null, 'actor_id' => null, 'subject_type' => $type, 'subject_id' => $id,
            'action' => $action, 'changes' => json_encode(['before' => $before], JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        throw new RuntimeException('Restore archived legacy references from a database backup to roll back these business rules.');
    }
};
