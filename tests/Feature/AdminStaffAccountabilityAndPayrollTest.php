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

    public function test_staff_can_confirm_without_changing_admin_assignment(): void
    {
        Mail::fake();

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

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $this->assertNull($booking->staff_id);
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

    public function test_admin_cannot_newly_assign_inactive_staff_to_booking(): void
    {
        $admin = Admin::factory()->create();
        $activeStaff = Staff::factory()->create([
            'name' => 'Active Guide',
            'username' => 'active.guide',
            'is_active' => true,
        ]);
        $inactiveStaff = Staff::factory()->create([
            'name' => 'Inactive Guide',
            'username' => 'inactive.guide',
            'is_active' => false,
        ]);
        $booking = Booking::factory()->create(['staff_id' => null]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee($activeStaff->username)
            ->assertDontSee($inactiveStaff->username);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.bookings.assign-staff', $booking), [
                'staff_id' => $inactiveStaff->id,
            ])
            ->assertSessionHasErrors('staff_id');

        $this->assertNull($booking->fresh()->staff_id);
    }

    public function test_existing_inactive_staff_assignment_remains_visible_and_can_be_retained(): void
    {
        $admin = Admin::factory()->create();
        $inactiveStaff = Staff::factory()->create([
            'name' => 'Former Guest Guide',
            'username' => 'former.guide',
            'is_active' => false,
        ]);
        $booking = Booking::factory()->create(['staff_id' => $inactiveStaff->id]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('Former Guest Guide')
            ->assertSee('Inactive, existing assignment');

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.bookings.assign-staff', $booking), [
                'staff_id' => $inactiveStaff->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertSame($inactiveStaff->id, $booking->fresh()->staff_id);
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
