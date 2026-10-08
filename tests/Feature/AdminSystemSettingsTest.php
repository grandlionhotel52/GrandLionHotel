<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_the_system_title(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('System Settings')
            ->assertSee('The Grand Lion Hotel');

        $this->actingAs($admin, 'admin')
            ->put(route('admin.settings.update'), [
                'hotel_name' => 'Sunset Bay Hotel',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'System title updated successfully.');

        $this->assertDatabaseHas('system_settings', [
            'setting_key' => 'hotel_name',
            'value' => 'Sunset Bay Hotel',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Welcome to Sunset Bay Hotel')
            ->assertSee('<title>Home - Sunset Bay Hotel</title>', false);
    }

    public function test_non_admin_cannot_manage_system_settings(): void
    {
        $this->get(route('admin.settings.edit'))
            ->assertRedirect(route('login'));

        $this->put(route('admin.settings.update'), [
            'hotel_name' => 'Unauthorized Hotel',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('system_settings', [
            'value' => 'Unauthorized Hotel',
        ]);
    }

    public function test_system_title_is_required_and_limited(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), ['hotel_name' => ''])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('hotel_name');

        $this->actingAs($admin, 'admin')
            ->from(route('admin.settings.edit'))
            ->put(route('admin.settings.update'), ['hotel_name' => str_repeat('A', 101)])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasErrors('hotel_name');
    }
}
