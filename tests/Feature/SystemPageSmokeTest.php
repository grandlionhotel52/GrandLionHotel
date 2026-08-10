<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemPageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_authentication_pages_render_without_server_errors(): void
    {
        $room = Room::factory()->create();

        $routes = [
            route('home'),
            route('rooms.index'),
            route('rooms.show', $room),
            route('about'),
            route('gallery'),
            route('terms'),
            route('blog.index'),
            route('blog.show', 'best-time-to-book-a-city-hotel'),
            route('login'),
            route('register'),
            route('password.request'),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_customer_pages_render_without_server_errors(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer');

        foreach ([
            route('profile.edit'),
            route('profile.security'),
            route('bookings.my'),
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_admin_pages_and_direct_edit_routes_render_without_server_errors(): void
    {
        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create(['admin_id' => $admin->getKey()]);
        $customer = Customer::factory()->create();
        $room = Room::factory()->create();

        $this->actingAs($admin, 'admin');

        $routes = [
            route('admin.dashboard'),
            route('admin.sales-report'),
            route('admin.occupancy-report'),
            route('admin.activity-logs.index'),
            route('admin.rooms.index'),
            route('admin.rooms.create'),
            route('admin.rooms.edit', $room),
            route('admin.rooms.date-discounts.index'),
            route('admin.bookings.index'),
            route('admin.users.index'),
            route('admin.users.edit', $customer),
            route('admin.staff.index'),
            route('admin.staff.create'),
            route('admin.staff.show', $staff),
            route('admin.staff.edit', $staff),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_staff_pages_render_without_server_errors(): void
    {
        $staff = Staff::factory()->create();

        $this->actingAs($staff, 'staff');

        foreach ([
            route('staff.dashboard'),
            route('staff.arrivals'),
            route('staff.bookings.index'),
            route('staff.bookings.create'),
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
    }
}
