<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminStaffAccountabilityAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_confirm_until_admin_assigns_them(): void
    {
        Mail::fake();

        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create();

        $booking = Booking::factory()->create([
            'customer_id' => null,
            'status' => 'pending',
            'staff_id' => null,
        ]);
        $booking->payment()->update(['status' => 'unpaid', 'method' => 'pending']);
        $booking->guestDetail()->update([
            'first_name' => 'Accountability',
            'last_name' => 'Guest',
        ]);

        $response = $this->actingAs($staff, 'staff')->patch(route('staff.bookings.confirm', $booking));

        $response->assertSessionHasErrors([
            'booking' => 'An admin must assign a staff member before this booking can proceed.',
        ]);

        $booking->refresh();
        $this->assertNull($booking->staff_id);
        $this->assertSame('pending', $booking->status);

        $this->actingAs($admin, 'admin')->patch(route('admin.bookings.assign-staff', $booking), [
            'staff_id' => $staff->id,
        ])->assertRedirect(route('admin.bookings.show', $booking));

        $this->actingAs($staff, 'staff')->patch(route('staff.bookings.confirm', $booking))
            ->assertRedirect(route('staff.bookings.show', $booking));

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_only_assigned_staff_can_confirm_a_booking(): void
    {
        $assignedStaff = Staff::factory()->create();
        $otherStaff = Staff::factory()->create();
        $booking = Booking::factory()->create([
            'status' => 'pending',
            'staff_id' => $assignedStaff->id,
        ]);

        $this->actingAs($otherStaff, 'staff')
            ->patch(route('staff.bookings.confirm', $booking))
            ->assertSessionHasErrors([
                'booking' => 'Only the assigned staff member can perform this booking action.',
            ]);

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_admin_can_assign_staff_owner_to_booking(): void
    {
        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create();

        $booking = Booking::factory()->create([
            'staff_id' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')->patch(route('admin.bookings.assign-staff', $booking), [
            'staff_id' => $staff->id,
        ]);

        $response->assertRedirect(route('admin.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame($staff->id, $booking->staff_id);
    }

    public function test_admin_can_view_only_assigned_customers_for_specific_staff(): void
    {
        $admin = Admin::factory()->create();
        $staffA = Staff::factory()->create();
        $staffB = Staff::factory()->create();

        $assignedOne = Booking::factory()->create([
            'customer_id' => null,
            'staff_id' => $staffA->id,
        ]);
        $assignedOne->guestDetail()->update([
            'first_name' => 'Assigned Guest',
            'last_name' => 'One',
            'email' => 'assigned.one@example.com',
        ]);

        $assignedTwo = Booking::factory()->create([
            'customer_id' => null,
            'staff_id' => $staffA->id,
        ]);
        $assignedTwo->guestDetail()->update([
            'first_name' => 'Assigned Guest',
            'last_name' => 'Two',
            'email' => 'assigned.two@example.com',
        ]);

        $otherStaff = Booking::factory()->create([
            'customer_id' => null,
            'staff_id' => $staffB->id,
        ]);
        $otherStaff->guestDetail()->update([
            'first_name' => 'Other Staff',
            'last_name' => 'Guest',
            'email' => 'other.staff@example.com',
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.staff.show', $staffA));

        $response->assertOk();
        $response->assertSee('Assigned Guest One');
        $response->assertSee('Assigned Guest Two');
        $response->assertDontSee('Other Staff Guest');
    }
}
