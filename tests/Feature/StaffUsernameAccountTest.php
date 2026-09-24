<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffUsernameAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_staff_with_a_username_instead_of_an_email(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('id="create_staff_username"', false)
            ->assertDontSee('id="create_staff_email"', false);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.staff.store'), [
                'first_name' => 'Front',
                'last_name' => 'Desk',
                'username' => 'front.desk',
                'phone' => '09171234567',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('staff', [
            'name' => 'Front Desk',
            'username' => 'front.desk',
            'admin_id' => $admin->id,
        ]);
    }

    public function test_staff_username_must_be_unique(): void
    {
        $admin = Admin::factory()->create();
        Staff::factory()->create(['username' => 'front.desk']);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), [
                'first_name' => 'Another',
                'last_name' => 'Staff',
                'username' => 'FRONT.DESK',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasErrors('username');
    }
}
