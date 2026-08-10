<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Staff;
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
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'paid_at' => null]);
        app(PaymentService::class)->charge($booking, 'cash');

        $this->actingAs($staff, 'staff')
            ->patch(route('staff.bookings.check-in', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertNotNull($booking->fresh()->actual_check_in_at);
    }
}
