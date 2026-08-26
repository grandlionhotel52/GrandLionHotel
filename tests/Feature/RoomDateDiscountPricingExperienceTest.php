<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomDateDiscount;
use App\Models\RoomStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomDateDiscountPricingExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_pricing_preview_returns_date_discount_breakdown(): void
    {
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        RoomDateDiscount::query()->create([
            'room_id' => $room->id,
            'discount_date' => now()->addDay()->toDateString(),
            'discount_percent' => 25,
        ]);

        $response = $this->getJson(route('rooms.pricing-preview', [
            'room' => $room,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
        ]));

        $response->assertOk();
        $response->assertJsonPath('pricing.average_nightly_rate', 1750);
        $response->assertJsonPath('pricing.chargeable_subtotal', 3500);
        $response->assertJsonPath('pricing.service_fee', 0);
        $response->assertJsonPath('pricing.local_tax', 175);
        $response->assertJsonPath('pricing.vat', 420);
        $response->assertJsonPath('pricing.total', 4095);
        $response->assertJsonPath('pricing.discount_amount', 500);
        $response->assertJsonPath('pricing.discounted_nights', 1);
        $response->assertJsonPath('availability.stay_available', true);

        $withBreakfast = $this->getJson(route('rooms.pricing-preview', [
            'room' => $room,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'meal_plan' => 'breakfast_included',
        ]));

        $withBreakfast->assertOk();
        $withBreakfast->assertJsonPath('pricing.service_fee_applies', true);
        $withBreakfast->assertJsonPath('pricing.service_fee', 280);
        $withBreakfast->assertJsonPath('pricing.total', 4375);
    }

    public function test_room_search_displays_selected_stay_discounted_pricing(): void
    {
        $room = $this->createRoom([
            'name' => 'Date Discount Room',
            'price_per_night' => 2000,
        ]);

        RoomDateDiscount::query()->create([
            'room_id' => $room->id,
            'discount_date' => now()->addDay()->toDateString(),
            'discount_percent' => 25,
        ]);

        $response = $this->get(route('rooms.index', [
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('Date Discount Room');
        $response->assertSee('1,750');
        $response->assertSee('4,095 total for 2 nights');
        $response->assertSee('Includes 5% local tax and 12% VAT. An 8% service charge applies only with breakfast.');
        $response->assertSee('Date discount on 1 night');
    }

    public function test_booking_create_page_uses_selected_stay_discounted_summary(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '+639000000123',
            'address_line' => '123 Test Street',
            'city' => 'Manila',
            'province' => 'Metro Manila (NCR)',
        ]);

        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        RoomDateDiscount::query()->create([
            'room_id' => $room->id,
            'discount_date' => now()->addDay()->toDateString(),
            'discount_percent' => 25,
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('bookings.create', [
            'room' => $room,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(),
            'guests' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('1,750');
        $response->assertSee('4,095');
        $response->assertSee('Service charge (8%, with breakfast only)');
        $response->assertSee('Local tax (5%)');
        $response->assertSee('VAT (12%, exclusive)');
        $response->assertSee('Date discount on 1 night');
    }

    private function createRoom(array $overrides = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'capacity' => 2,
            'room_status_id' => $cleanStatusId,
        ], $overrides));
    }
}
