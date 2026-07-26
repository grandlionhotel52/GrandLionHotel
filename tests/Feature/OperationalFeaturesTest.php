<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationalFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_managed_room_image(): void
    {
        Storage::fake('public');
        $admin = Admin::factory()->create();
        $status = RoomStatus::query()->where('slug', 'clean')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')->post(route('admin.rooms.store'), [
            'name' => 'Uploaded Image Room',
            'type' => 'Suite',
            'view_type' => 'Pool View',
            'description' => 'Room with a managed upload.',
            'price_per_night' => 4500,
            'room_status_id' => $status->id,
            'image_upload' => UploadedFile::fake()->create('room.webp', 25, 'image/webp'),
        ]);

        $response->assertRedirect(route('admin.rooms.index'));

        $room = Room::query()->where('name', 'Uploaded Image Room')->firstOrFail();
        $this->assertStringStartsWith('room-images/', $room->image);
        Storage::disk('public')->assertExists($room->image);
    }

    public function test_booking_changes_are_audited_and_notify_the_customer(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $booking->update(['status' => 'confirmed']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'subject_type' => 'Booking',
            'subject_id' => $booking->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => Customer::class,
            'notifiable_id' => $customer->id,
        ]);
    }

    public function test_admin_can_open_the_occupancy_report(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.occupancy-report', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->endOfMonth()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Occupancy Report')
            ->assertSee('Room nights sold');
    }

    public function test_legacy_room_search_redirects_to_the_canonical_route(): void
    {
        $this->get(route('rooms.search', ['type' => 'Suite']))
            ->assertRedirect(route('rooms.index', ['type' => 'Suite']))
            ->assertStatus(301);
    }
}
