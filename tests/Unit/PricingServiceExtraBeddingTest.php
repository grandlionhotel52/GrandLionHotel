<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\RoomDateDiscount;
use App\Models\RoomStatus;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PricingServiceExtraBeddingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pricing.extra_bedding_fee_per_night' => 500]);

        DB::table('room_status')->updateOrInsert(
            ['slug' => 'clean'],
            [
                'name' => 'Clean',
                'description' => 'Ready for booking',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function test_quote_stay_adds_extra_bedding_fee_per_night(): void
    {
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        $quote = app(PricingService::class)->quoteStay(
            $room,
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
            3,
            true
        );

        $this->assertSame(1, $quote['extra_bedding_count']);
        $this->assertSame(1000.0, $quote['extra_bedding_total']);
        $this->assertSame(5000.0, $quote['chargeable_subtotal']);
        $this->assertSame(400.0, $quote['service_fee']);
        $this->assertSame(4821.43, $quote['net_sales']);
        $this->assertSame(241.07, $quote['local_tax']);
        $this->assertSame(578.57, $quote['vat']);
        $this->assertSame(5641.07, $quote['total']);
        $this->assertSame(2500.0, $quote['average_nightly_rate']);
    }

    public function test_quote_stay_combines_date_discounts_and_extra_bedding_surcharge(): void
    {
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        RoomDateDiscount::query()->create([
            'room_id' => $room->id,
            'discount_date_start' => now()->addDay()->toDateString(),
            'discount_date_end' => now()->addDay()->toDateString(),
            'discount_percent' => 25,
        ]);

        $quote = app(PricingService::class)->quoteStay(
            $room,
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
            3,
            true
        );

        $this->assertSame(3500.0, $quote['room_total']);
        $this->assertSame(500.0, $quote['discount_amount']);
        $this->assertSame(1000.0, $quote['extra_bedding_total']);
        $this->assertSame(5076.96, $quote['total']);
    }

    public function test_service_charge_only_applies_when_breakfast_is_selected(): void
    {
        $room = $this->createRoom(['price_per_night' => 2000]);
        $checkIn = now()->addDay()->toDateString();
        $checkOut = now()->addDays(2)->toDateString();

        $roomOnly = app(PricingService::class)->quoteStay($room, $checkIn, $checkOut, 2);
        $withBreakfast = app(PricingService::class)->quoteStay($room, $checkIn, $checkOut, 2, true);

        $this->assertFalse($roomOnly['service_fee_applies']);
        $this->assertSame(0.0, $roomOnly['service_fee']);
        $this->assertSame(2089.29, $roomOnly['total']);
        $this->assertTrue($withBreakfast['service_fee_applies']);
        $this->assertSame(160.0, $withBreakfast['service_fee']);
        $this->assertSame(2256.43, $withBreakfast['total']);
    }

    public function test_vat_inclusive_formula_extracts_vat_and_adds_local_tax(): void
    {
        $charges = app(PricingService::class)->statutoryCharges(1008);

        $this->assertSame(1008.0, $charges['vat_inclusive_amount']);
        $this->assertSame(108.0, $charges['vat']);
        $this->assertSame(900.0, $charges['net_sales']);
        $this->assertSame(45.0, $charges['local_tax']);
        $this->assertSame(1053.0, $charges['total']);
    }

    public function test_senior_and_pwd_formula_removes_vat_before_twenty_percent_discount(): void
    {
        $pricing = app(PricingService::class);
        $quote = $pricing->statutoryCharges(1008);

        foreach (['senior', 'pwd'] as $type) {
            $bill = $pricing->applyDiscount($quote, $type, 0.20);

            $this->assertTrue($bill['vat_exempt']);
            $this->assertSame(108.0, $bill['vat_exemption']);
            $this->assertSame(900.0, $bill['vat_exempt_sales']);
            $this->assertSame(180.0, $bill['discount_amount_applied']);
            $this->assertSame(720.0, $bill['net_sales']);
            $this->assertSame(0.0, $bill['vat']);
            $this->assertSame(36.0, $bill['local_tax']);
            $this->assertSame(756.0, $bill['total']);
        }
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
