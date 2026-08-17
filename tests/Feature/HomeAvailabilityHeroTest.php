<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAvailabilityHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_hero_contains_a_functional_room_availability_search(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('A first look at your next stay')
            ->assertSee('Preview our rooms, compare rates, and check your preferred dates.')
            ->assertSee('id="homeAvailabilitySearch"', false)
            ->assertSee('action="'.route('rooms.index').'"', false)
            ->assertSee('name="available_only" value="1"', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="check_in"', false)
            ->assertSee('name="check_out"', false)
            ->assertSee('Bright premium hotel bedroom')
            ->assertSee('Comfortable modern hotel room interior')
            ->assertSee('Elegant hotel suite bedroom interior')
            ->assertDontSee('Hotel infinity pool')
            ->assertDontSee('Luxury hotel exterior')
            ->assertSee('Check availability');
    }

    public function test_home_no_longer_renders_the_duplicate_quick_search_panel(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('id="homeQuickSearch"', false)
            ->assertDontSee('Quick Search');
    }

    public function test_guest_home_is_a_teaser_that_keeps_room_discovery_public(): void
    {
        Room::factory()->count(4)->sequence(
            ['name' => 'Preview Room One'],
            ['name' => 'Preview Room Two'],
            ['name' => 'Preview Room Three'],
            ['name' => 'Full Access Room Four'],
        )->create(['is_available' => true]);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Three Rooms Worth Discovering')
            ->assertSee('Ready to turn your preview into a stay?')
            ->assertSee('Sign in to continue')
            ->assertDontSee('Browse by Room Type')
            ->assertDontSee('Frequently Asked Questions')
            ->assertSee('action="'.route('rooms.index').'"', false);

        $this->assertSame(3, substr_count($response->getContent(), 'data-featured-room'));
    }

    public function test_signed_in_customer_sees_the_full_home_experience(): void
    {
        $customer = Customer::factory()->create();
        Room::factory()->count(4)->sequence(
            ['name' => 'Customer Room One'],
            ['name' => 'Customer Room Two'],
            ['name' => 'Customer Room Three'],
            ['name' => 'Customer Room Four'],
        )->create(['is_available' => true]);

        $response = $this->actingAs($customer, 'customer')->get(route('home'));

        $response->assertOk()
            ->assertDontSee('Three Rooms Worth Discovering')
            ->assertDontSee('Ready to turn your preview into a stay?')
            ->assertSee('Find your perfect stay')
            ->assertSee('Browse by Room Type')
            ->assertSee('Frequently Asked Questions')
            ->assertSee('Starting rate');

        $this->assertGreaterThan(3, substr_count($response->getContent(), 'data-featured-room'));
    }
}
