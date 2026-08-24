<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Staff;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_and_open_activity_logs(): void
    {
        $admin = Admin::factory()->create(['name' => 'Audit Administrator']);
        $room = Room::factory()->create(['name' => 'Original Room Name']);

        $this->actingAs($admin, 'admin');
        $room->update(['name' => 'Updated Room Name']);

        $log = ActivityLog::query()
            ->where('subject_type', 'Room')
            ->where('subject_id', $room->getKey())
            ->where('action', 'updated')
            ->latest('activity_log_id')
            ->firstOrFail();

        $this->get(route('admin.activity-logs.index', [
            'action' => 'updated',
            'subject_type' => 'Room',
            'actor_type' => 'Admin',
        ]))
            ->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('Audit Administrator')
            ->assertSee('Updated')
            ->assertSee('Room');

        $this->get(route('admin.activity-logs.show', $log))
            ->assertOk()
            ->assertSee('Before and after')
            ->assertSee('Original Room Name')
            ->assertSee('Updated Room Name')
            ->assertSee('Audit Administrator');
    }

    public function test_activity_log_redacts_sensitive_values(): void
    {
        $admin = Admin::factory()->create();
        $room = Room::factory()->create();

        $this->actingAs($admin, 'admin');

        $log = app(AuditLogger::class)->recordModel($room, 'updated', [
            'password' => 'old-secret',
        ], [
            'password' => 'new-secret',
            'payment_proof_path' => 'payment-proofs/private.jpg',
        ]);

        $this->assertSame('[redacted]', data_get($log->changes, 'before.password'));
        $this->assertSame('[redacted]', data_get($log->changes, 'after.password'));
        $this->assertSame('[redacted]', data_get($log->changes, 'after.payment_proof_path'));
        $this->assertStringNotContainsString('new-secret', json_encode($log->changes));
    }

    public function test_login_and_logout_events_are_recorded(): void
    {
        $admin = Admin::factory()->create();

        event(new Login('admin', $admin, false));
        event(new Logout('admin', $admin));

        $this->assertDatabaseHas('activity_logs', [
            'actor_type' => 'Admin',
            'actor_id' => $admin->getKey(),
            'action' => 'logged_in',
            'subject_type' => 'Admin',
            'subject_id' => $admin->getKey(),
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'actor_type' => 'Admin',
            'actor_id' => $admin->getKey(),
            'action' => 'logged_out',
        ]);
    }

    public function test_staff_cannot_access_admin_activity_logs(): void
    {
        $staff = Staff::factory()->create();

        $this->actingAs($staff, 'staff')
            ->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_staff_and_customer_account_changes_are_recorded(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $staff = Staff::factory()->create(['name' => 'Original Staff']);
        $customer = Customer::factory()->create(['name' => 'Original Customer']);

        $staff->update(['name' => 'Updated Staff']);
        $customer->update(['name' => 'Updated Customer']);

        $staffId = $staff->getKey();
        $customerId = $customer->getKey();
        $staff->delete();
        $customer->delete();

        foreach ([
            ['subject_type' => 'Staff', 'subject_id' => $staffId],
            ['subject_type' => 'Customer', 'subject_id' => $customerId],
        ] as $subject) {
            foreach (['created', 'updated', 'deleted'] as $action) {
                $this->assertDatabaseHas('activity_logs', $subject + [
                    'actor_type' => 'Admin',
                    'actor_id' => $admin->getKey(),
                    'action' => $action,
                ]);
            }
        }

        $this->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Staff')
            ->assertSee('Customer');
    }
}
