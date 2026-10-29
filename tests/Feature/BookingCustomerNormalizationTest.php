<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingCustomerNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_details_store_only_booking_fields_and_follow_customer_profile_updates(): void
    {
        $customer = Customer::factory()->create(['name' => 'Original Customer']);
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);

        $this->assertEqualsCanonicalizing([
            'booking_detail_id', 'booking_id', 'adults', 'kids', 'payment_preference',
            'created_at', 'updated_at', 'meal_plan',
        ], Schema::getColumnListing('booking_details'));
        $this->assertFalse(Schema::hasTable('booking_guest_details'));

        $customer->update([
            'name' => 'Updated Customer',
            'email' => 'updated@example.com',
            'phone' => '09171234567',
            'address_line' => 'Updated Address',
        ]);
        $booking->refresh();

        $this->assertSame('Updated Customer', $booking->guestName());
        $this->assertSame('updated@example.com', $booking->guestEmail());
        $this->assertSame('09171234567', $booking->guestPhone());
        $this->assertSame('Updated Address', $booking->reservation_meta['street_address']);
        $this->assertTrue($booking->details->booking->is($booking));
    }

    public function test_walk_in_booking_reuses_existing_customer_without_overwriting_their_profile(): void
    {
        $customer = Customer::factory()->create(['name' => 'Existing Customer', 'email' => 'existing@example.com']);
        $staff = Staff::factory()->create();
        $room = Room::factory()->create(['is_available' => true]);

        $this->actingAs($staff, 'staff')->post(route('staff.bookings.store'), [
            'customer_name' => 'Submitted Name',
            'customer_email' => 'EXISTING@example.com',
            'room_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guests' => 2,
            'extra_bedding_confirmed' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $booking = Booking::query()->sole();
        $this->assertSame($customer->id, $booking->customer_id);
        $this->assertSame('Existing Customer', $booking->guestName());
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('booking_details', ['booking_id' => $booking->id, 'adults' => 2]);
    }

    public function test_migration_moves_walk_in_contacts_fills_missing_profile_fields_and_archives_conflicts(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config(['database.connections.contact_normalization_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('contact_normalization_test');

        try {
            Schema::create('customers', function (Blueprint $table): void {
                $table->bigIncrements('customer_id');
                $table->string('name')->nullable();
                $table->string('email')->nullable()->unique();
                foreach (['phone', 'address_line', 'city', 'province'] as $column) {
                    $table->string($column)->nullable();
                }
                $table->string('password')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            Schema::create('bookings', function (Blueprint $table): void {
                $table->bigIncrements('booking_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->foreign('customer_id')->references('customer_id')->on('customers');
            });
            Schema::create('booking_guest_details', function (Blueprint $table): void {
                $table->bigIncrements('guest_detail_id');
                $table->unsignedBigInteger('booking_id')->unique();
                $table->foreign('booking_id')->references('booking_id')->on('bookings');
                foreach (['first_name', 'last_name', 'email', 'phone', 'address_line', 'street_address_line_2', 'city', 'province', 'postal_code'] as $column) {
                    $table->string($column)->nullable();
                }
                $table->unsignedInteger('adults')->default(2);
                $table->unsignedInteger('kids')->default(0);
                $table->string('meal_plan')->default('room_only');
                $table->string('payment_preference')->nullable();
                $table->timestamps();
            });
            Schema::create('activity_logs', function (Blueprint $table): void {
                $table->bigIncrements('activity_log_id');
                $table->string('actor_type')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action');
                $table->string('subject_type');
                $table->unsignedBigInteger('subject_id');
                $table->json('changes')->nullable();
                $table->timestamps();
            });

            DB::table('customers')->insert(['customer_id' => 1, 'name' => 'Current Name', 'email' => 'current@example.com', 'phone' => null, 'password' => 'unchanged-hash']);
            DB::table('bookings')->insert([
                ['booking_id' => 10, 'customer_id' => 1],
                ['booking_id' => 11, 'customer_id' => null],
                ['booking_id' => 12, 'customer_id' => null],
            ]);
            DB::table('booking_guest_details')->insert([
                ['booking_id' => 10, 'first_name' => 'Old', 'last_name' => 'Name', 'email' => 'old@example.com', 'phone' => '09171234567', 'postal_code' => '2400', 'street_address_line_2' => 'Unit 2'],
                ['booking_id' => 11, 'first_name' => 'Walk In', 'last_name' => 'Guest', 'email' => null, 'phone' => '09179999999', 'postal_code' => null, 'street_address_line_2' => null],
                ['booking_id' => 12, 'first_name' => 'Current', 'last_name' => 'Name', 'email' => 'current@example.com', 'phone' => null, 'postal_code' => null, 'street_address_line_2' => null],
            ]);

            $migration = require database_path('migrations/2026_10_30_000001_normalize_booking_customer_information.php');
            $migration->up();

            $this->assertDatabaseCount('customers', 2);
            $this->assertDatabaseHas('customers', ['customer_id' => 1, 'name' => 'Current Name', 'email' => 'current@example.com', 'phone' => '09171234567', 'postal_code' => '2400', 'street_address_line_2' => 'Unit 2', 'password' => 'unchanged-hash']);
            $walkInId = DB::table('bookings')->where('booking_id', 11)->value('customer_id');
            $this->assertDatabaseHas('customers', ['customer_id' => $walkInId, 'name' => 'Walk In Guest', 'phone' => '09179999999', 'password' => null]);
            $this->assertDatabaseHas('bookings', ['booking_id' => 12, 'customer_id' => 1]);
            $this->assertDatabaseCount('booking_details', 3);
            $this->assertFalse(Schema::hasColumn('booking_details', 'email'));
            $audit = DB::table('activity_logs')->where('subject_id', 10)->sole();
            $this->assertSame('customer_details_normalized', $audit->action);
            $this->assertSame(['name' => 'Old Name', 'email' => 'old@example.com'], json_decode($audit->changes, true)['before']);
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('contact_normalization_test');
        }
    }
}
