<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorContrastStylesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_layout_includes_shared_contrast_improvements(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('--accessible-brand-text: #172033', false)
            ->assertSee('--accessible-muted: #596273', false)
            ->assertSee('.form-control::placeholder', false)
            ->assertSee('.btn-ta:hover', false);
    }

    public function test_admin_and_staff_layouts_include_shared_contrast_improvements(): void
    {
        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('--accessible-brand-text: #172033', false);

        auth('admin')->logout();

        $this->actingAs($staff, 'staff')
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('--accessible-brand-text: #172033', false);
    }
}
