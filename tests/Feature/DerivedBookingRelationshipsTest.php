<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingBill;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DerivedBookingRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_reaches_its_booking_through_a_distinct_bill_identifier(): void
    {
        $booking = Booking::factory()->make(['status' => 'pending']);
        $booking->save();
        $bill = BookingBill::query()->forceCreate([
            'booking_bill_id' => 9000, 'booking_id' => $booking->id, 'total_amount' => 1000,
        ]);
        $payment = $booking->createPayment(['amount' => 1000, 'status' => 'unpaid', 'method' => 'cash']);

        $this->assertSame(9000, $payment->booking_bill_id);
        $this->assertNotSame($booking->id, $bill->getKey());
        $this->assertTrue($payment->booking->is($booking));
        $this->assertTrue($payment->bill->is($bill));
        $this->assertTrue($booking->fresh()->payment->is($payment));
        $this->assertSame(1, $booking->payments()->count());
    }

    public function test_only_assigned_staff_can_respond_to_bedding_requests(): void
    {
        $assigned = Staff::factory()->create();
        $other = Staff::factory()->create();
        $booking = Booking::factory()->create(['status' => 'pending', 'staff_id' => $assigned->id]);
        $request = $booking->extraBeddingRequest()->create([
            'requested_count' => 1, 'approved_count' => 0, 'status' => 'pending',
            'customer_message' => 'One bed please.', 'requested_at' => now(),
        ]);
        $response = ['extra_bedding_status' => 'approved', 'approved_count' => 1, 'staff_response' => 'Approved.'];
        $this->actingAs($other, 'staff')->patch(route('staff.bookings.extra-bedding-response', $booking), $response)->assertForbidden();
        $this->assertSame('pending', $request->fresh()->status);
        $this->actingAs($assigned, 'staff')->patch(route('staff.bookings.extra-bedding-response', $booking), $response)->assertSessionHasNoErrors();
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertTrue($request->booking->assignedStaff->is($assigned));
        $this->assertFalse(Schema::hasColumn('booking_extra_bedding_requests', 'responded_by_staff_id'));
    }

    public function test_migration_links_valid_reviews_and_archives_unlinked_reviews_and_differing_responders(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.reference_migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('reference_migration_test');
        try {
            Schema::create('bookings', function (Blueprint $table): void {
                $table->bigIncrements('booking_id');
                $table->unsignedBigInteger('room_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->string('status');
                $table->date('check_out');
            });
            Schema::create('room_reviews', function (Blueprint $table): void {
                $table->bigIncrements('room_review_id');
                $table->unsignedBigInteger('booking_id')->nullable()->unique();
                $table->foreign('booking_id')->references('booking_id')->on('bookings');
                $table->unsignedBigInteger('room_id');
                $table->unsignedBigInteger('customer_id');
                $table->integer('rating');
                $table->text('comment');
                $table->unique(['room_id', 'customer_id']);
            });
            Schema::create('booking_extra_bedding_requests', function (Blueprint $table): void {
                $table->bigIncrements('booking_extra_bedding_request_id');
                $table->unsignedBigInteger('booking_id');
                $table->unsignedBigInteger('responded_by_staff_id')->nullable();
            });
            Schema::create('activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('actor_type')->nullable();
                $table->integer('actor_id')->nullable();
                $table->string('subject_type');
                $table->integer('subject_id');
                $table->string('action');
                $table->json('changes');
                $table->timestamps();
            });
            DB::table('bookings')->insert([
                ['booking_id' => 1, 'room_id' => 10, 'customer_id' => 20, 'staff_id' => 30, 'status' => 'completed', 'check_out' => '2026-10-01'],
                ['booking_id' => 2, 'room_id' => 11, 'customer_id' => 21, 'staff_id' => null, 'status' => 'confirmed', 'check_out' => '2026-11-01'],
            ]);
            DB::table('room_reviews')->insert([
                ['booking_id' => null, 'room_id' => 10, 'customer_id' => 20, 'rating' => 5, 'comment' => 'Completed stay.'],
                ['booking_id' => null, 'room_id' => 99, 'customer_id' => 99, 'rating' => 3, 'comment' => 'No booking.'],
            ]);
            DB::table('booking_extra_bedding_requests')->insert([
                ['booking_id' => 1, 'responded_by_staff_id' => 40],
                ['booking_id' => 2, 'responded_by_staff_id' => 50],
            ]);
            $migration = require database_path('migrations/2026_10_30_000004_derive_review_and_bedding_references_from_bookings.php');
            $migration->up();

            $this->assertDatabaseHas('room_reviews', ['booking_id' => 1, 'comment' => 'Completed stay.']);
            $this->assertDatabaseCount('room_reviews', 1);
            $this->assertDatabaseHas('activity_logs', ['action' => 'unlinked_review_archived', 'subject_type' => 'RoomReview']);
            $this->assertDatabaseHas('activity_logs', ['action' => 'legacy_bedding_responder_archived']);
            $this->assertDatabaseHas('bookings', ['booking_id' => 1, 'staff_id' => 30]);
            $this->assertDatabaseHas('bookings', ['booking_id' => 2, 'staff_id' => 50]);
            $this->assertFalse(Schema::hasColumn('room_reviews', 'room_id'));
            $this->assertFalse(Schema::hasColumn('room_reviews', 'customer_id'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('reference_migration_test');
        }
    }
}
