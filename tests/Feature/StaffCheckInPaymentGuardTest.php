<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Staff;
use App\Models\RoomStatus;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffCheckInPaymentGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_check_in_an_unpaid_guest(): void
    {
        $staff = Staff::factory()->create();
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'actual_check_in_at' => null,
            'staff_id' => $staff->id,
        ]);
        $booking->payment()->update([
            'status' => 'unpaid',
            'paid_at' => null,
        ]);

        $this->actingAs($staff, 'staff')
            ->patch(route('staff.bookings.check-in', $booking))
            ->assertSessionHasErrors([
                'booking' => 'Payment must be recorded as paid before the guest can check in.',
            ]);

        $this->assertNull($booking->fresh()->actual_check_in_at);
    }

    public function test_staff_can_check_in_after_payment_is_recorded(): void
    {
        $staff = Staff::factory()->create();
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'actual_check_in_at' => null,
            'staff_id' => $staff->id,
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'paid_at' => null]);
        app(PaymentService::class)->charge($booking, 'cash');

        $this->actingAs($staff, 'staff')
            ->patch(route('staff.bookings.check-in', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertNotNull($booking->fresh()->actual_check_in_at);
    }

    public function test_staff_can_check_in_without_guest_care_assignment(): void
    {
        $staff = Staff::factory()->create();
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'staff_id' => null,
            'check_in' => today(),
            'check_out' => today()->addDay(),
        ]);
        app(PaymentService::class)->charge($booking, 'cash');

        $this->actingAs($staff, 'staff')
            ->patch(route('staff.bookings.check-in', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertNotNull($booking->fresh()->actual_check_in_at);
    }

    public function test_guest_care_assignment_does_not_restrict_other_staff_from_checking_in_the_guest(): void
    {
        $assigned = Staff::factory()->create();
        $other = Staff::factory()->create();
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'staff_id' => $assigned->id,
            'check_in' => today(),
            'check_out' => today()->addDay(),
        ]);
        app(PaymentService::class)->charge($booking, 'cash');

        $this->actingAs($other, 'staff')
            ->patch(route('staff.bookings.check-in', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertNotNull($booking->fresh()->actual_check_in_at);
        $this->assertSame($assigned->id, $booking->fresh()->staff_id);
    }

    public function test_any_staff_can_check_out_and_mark_room_clean_without_changing_guest_care_staff(): void
    {
        $guestCareStaff = Staff::factory()->create();
        $operatingStaff = Staff::factory()->create();
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'staff_id' => $guestCareStaff->id,
            'check_in' => today()->subDay(),
            'check_out' => today(),
            'actual_check_in_at' => now()->subHour(),
            'actual_check_out_at' => null,
        ]);
        app(PaymentService::class)->charge($booking, 'cash');

        $this->actingAs($operatingStaff, 'staff')
            ->patch(route('staff.bookings.check-out', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $dirtyStatusId = RoomStatus::query()->where('slug', 'dirty')->value('room_status_id');
        $this->assertSame((int) $dirtyStatusId, (int) $booking->room->fresh()->room_status_id);

        $this->patch(route('staff.bookings.room-clean', $booking))->assertRedirect(route('staff.bookings.show', $booking));

        $cleanStatusId = RoomStatus::query()->where('slug', 'clean')->value('room_status_id');
        $this->assertSame((int) $cleanStatusId, (int) $booking->room->fresh()->room_status_id);
        $this->assertSame($guestCareStaff->id, $booking->fresh()->staff_id);
    }
}
