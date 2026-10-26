<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\PromoCode;
use Database\Seeders\PromoCodeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_customer_sees_the_welcome_code_in_the_promos_modal(): void
    {
        $this->seed(PromoCodeSeeder::class);
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-bs-target="#promotionsModal"', false)
            ->assertSee('WELCOME10')
            ->assertSee('New customer')
            ->assertSee('Welcome offer for your first hotel booking.');
    }

    public function test_admin_activated_seasonal_code_appears_but_inactive_code_does_not(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')->post(route('admin.promo-codes.store'), [
            'code' => 'CHRISTMAS20',
            'description' => 'Christmas offer for December stays.',
            'discount_percent' => 20,
            'starts_at' => today()->subDay()->toDateString(),
            'ends_at' => today()->addMonth()->toDateString(),
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('promo_codes', [
            'code' => 'CHRISTMAS20',
            'new_customers_only' => false,
            'is_active' => true,
        ]);

        PromoCode::query()->create([
            'code' => 'HIDDEN25',
            'description' => 'This should not be shown.',
            'discount_percent' => 25,
            'is_active' => false,
        ]);
        PromoCode::query()->create([
            'code' => 'UPCOMING30',
            'description' => 'This offer has not started.',
            'discount_percent' => 30,
            'starts_at' => today()->addWeek(),
            'ends_at' => today()->addMonth(),
            'is_active' => true,
        ]);
        PromoCode::query()->create([
            'code' => 'EXPIRED35',
            'description' => 'This offer has expired.',
            'discount_percent' => 35,
            'starts_at' => today()->subMonth(),
            'ends_at' => today()->subDay(),
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create();
        $this->assertTrue(PromoCode::query()->availableFor($customer)->where('code', 'CHRISTMAS20')->exists());

        $this->actingAs($customer, 'customer')
            ->get(route('home'))
            ->assertOk()
            ->assertSee('CHRISTMAS20')
            ->assertSee('Christmas offer for December stays.')
            ->assertDontSee('HIDDEN25')
            ->assertDontSee('UPCOMING30')
            ->assertDontSee('EXPIRED35');
    }

    public function test_returning_customer_does_not_see_new_customer_only_codes(): void
    {
        $this->seed(PromoCodeSeeder::class);
        $customer = Customer::factory()->create();
        Booking::factory()->create(['customer_id' => $customer->getKey()]);
        PromoCode::query()->create([
            'code' => 'HOLIDAY15',
            'description' => 'Holiday offer for every customer.',
            'discount_percent' => 15,
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee('WELCOME10')
            ->assertSee('HOLIDAY15');

        $this->assertFalse(
            PromoCode::query()->availableFor($customer)->where('code', 'WELCOME10')->exists()
        );
    }
}
