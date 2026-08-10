<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAvailabilityHeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_hero_contains_a_functional_room_availability_search(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Find your perfect stay')
            ->assertSee('Choose your dates and check available rooms.')
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
}
