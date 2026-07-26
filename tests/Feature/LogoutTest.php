<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout_via_post(): void
    {
        $user = Customer::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_logout_via_get_is_not_allowed(): void
    {
        $user = Customer::factory()->create();

        $response = $this->actingAs($user)->get(route('logout'));

        $response->assertMethodNotAllowed();
        $this->assertAuthenticatedAs($user);
    }
}
