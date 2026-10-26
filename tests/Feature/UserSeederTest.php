<?php

namespace Tests\Feature;

use App\Models\Admin;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_initial_administrator_for_an_empty_database(): void
    {
        $this->seed(UserSeeder::class);

        $admin = Admin::query()->sole();

        $this->assertSame('admin', $admin->username);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('Password', $admin->password));
    }

    public function test_it_does_not_reset_an_existing_administrator_by_default(): void
    {
        $this->seed(UserSeeder::class);

        $admin = Admin::query()->sole();
        $admin->forceFill(['password' => Hash::make('ChangedPassword')])->save();

        $this->seed(UserSeeder::class);

        $this->assertTrue(Hash::check('ChangedPassword', $admin->fresh()->password));
    }
}
