<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_operational_sections_and_recent_bookings(): void
    {
        $admin = Admin::factory()->create();

        $room = Room::factory()->create([
            'is_available' => true,
        ]);

        $booking = Booking::factory()->create([
            'room_id' => $room->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Open Booking Desk');
        $response->assertSee('Priority Queues');
        $response->assertSee('Quick Actions');
        $response->assertSee('Latest Booking Activity');
        $response->assertSee('#'.$booking->id);
    }
}
