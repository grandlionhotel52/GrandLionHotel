<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_review_a_room_after_a_completed_stay(): void
    {
        $customer = Customer::factory()->create(['name' => 'Maria Santos']);
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'actual_check_in_at' => now()->subDays(2),
            'actual_check_out_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->post(route('bookings.review.store', $booking), [
                'rating' => 5,
                'comment' => 'The room was spotless, quiet, and very comfortable.',
            ]);

        $response->assertRedirect(route('bookings.show', $booking).'#guest-review');
        $response->assertSessionHas('status', 'Thank you. Your room review has been saved.');
        $this->assertDatabaseHas('room_reviews', [
            'booking_id' => $booking->id,
            'rating' => 5,
            'comment' => 'The room was spotless, quiet, and very comfortable.',
        ]);

        $this->get(route('rooms.show', $booking->room))
            ->assertOk()
            ->assertSee('5.0 / 5')
            ->assertSee('Maria S.')
            ->assertSee('Verified stay')
            ->assertSee('The room was spotless, quiet, and very comfortable.');
    }

    public function test_review_is_updated_instead_of_duplicated(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        $this->actingAs($customer, 'customer')->post(route('bookings.review.store', $booking), [
            'rating' => 3,
            'comment' => 'The first version of my feedback.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($customer, 'customer')->post(route('bookings.review.store', $booking), [
            'rating' => 4,
            'comment' => 'Updated after speaking with the hotel team.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('room_reviews', 1);
        $this->assertDatabaseHas('room_reviews', [
            'booking_id' => $booking->id,
            'rating' => 4,
            'comment' => 'Updated after speaking with the hotel team.',
        ]);
    }

    public function test_unfinished_or_unowned_booking_cannot_be_reviewed(): void
    {
        $owner = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $pendingBooking = Booking::factory()->create([
            'customer_id' => $owner->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner, 'customer')->post(route('bookings.review.store', $pendingBooking), [
            'rating' => 5,
            'comment' => 'This stay is not finished.',
        ])->assertSessionHasErrors('review');

        $completedBooking = Booking::factory()->create([
            'customer_id' => $owner->id,
            'status' => 'completed',
        ]);

        $this->actingAs($otherCustomer, 'customer')->post(route('bookings.review.store', $completedBooking), [
            'rating' => 1,
            'comment' => 'This booking does not belong to me.',
        ])->assertForbidden();

        $this->assertDatabaseCount('room_reviews', 0);
    }

    public function test_rating_and_comment_are_validated(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        $this->actingAs($customer, 'customer')->post(route('bookings.review.store', $booking), [
            'rating' => 6,
            'comment' => '',
        ])->assertSessionHasErrors(['rating', 'comment']);

        $this->assertDatabaseCount('room_reviews', 0);
    }
}
