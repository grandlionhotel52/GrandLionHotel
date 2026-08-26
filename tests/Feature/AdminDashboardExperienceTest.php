<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
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

        $todayBooking = Booking::factory()->create(['room_id' => $room->id]);
        $todayBooking->payment()->updateOrCreate(['booking_id' => $todayBooking->id], [
            'amount' => 1200,
            'method' => Payment::METHOD_CASH,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $monthBooking = Booking::factory()->create(['room_id' => $room->id]);
        $monthBooking->payment()->updateOrCreate(['booking_id' => $monthBooking->id], [
            'amount' => 800,
            'method' => Payment::METHOD_CASH,
            'status' => 'paid',
            'paid_at' => now()->startOfMonth(),
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard');
        $response->assertSee('Open Booking Desk');
        $response->assertSee('Priority Queues');
        $response->assertSee('Quick Actions');
        $response->assertSee('Latest Booking Activity');
        $response->assertSee('Daily');
        $response->assertSee('Month to date');
        $response->assertSee('Year to date');
        $response->assertSee('&#8369;1,200.00', false);
        $response->assertSee('&#8369;2,000.00', false);
        $response->assertSee('#'.$booking->id);
    }
}
