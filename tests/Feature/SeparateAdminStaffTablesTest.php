<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Staff;
use App\Support\AccountDirectory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeparateAdminStaffTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_with_overlapping_ids_use_their_own_tables_and_guards(): void
    {
        $admin = Admin::factory()->create(['admin_id' => 100, 'username' => 'hotel.admin']);
        $staff = Staff::factory()->create([
            'staff_id' => 100,
            'username' => 'front.desk',
            'created_by' => $admin->id,
        ]);

        $this->assertFalse(Schema::hasTable('employees'));
        $this->assertTrue($staff->createdByAdmin->is($admin));
        $this->assertTrue($admin->createdStaff->first()->is($staff));
        $this->assertTrue(AccountDirectory::findByLogin('HOTEL.ADMIN')->is($admin));
        $this->assertTrue(AccountDirectory::findByLogin('FRONT.DESK')->is($staff));

        $this->post(route('login'), ['email' => 'front.desk', 'password' => 'password']);
        $this->assertAuthenticatedAs($staff, 'staff');
        $this->assertGuest('admin');
    }

    public function test_migration_preserves_existing_accounts_passwords_and_references(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config(['database.connections.account_split_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('account_split_test');

        try {
            Schema::create('employees', function (Blueprint $table): void {
                $table->bigIncrements('employee_id');
                $table->string('role');
                $table->string('name');
                $table->string('username')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->timestamp('password_changed_at')->nullable();
                $table->rememberToken();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('employee_id')->on('employees')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            Schema::create('bookings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('staff_id')->nullable();
                $table->unsignedBigInteger('reschedule_approved_by_admin_id')->nullable();
                $table->foreign('staff_id')->references('employee_id')->on('employees')->nullOnDelete();
                $table->foreign('reschedule_approved_by_admin_id')->references('employee_id')->on('employees')->nullOnDelete();
            });
            $password = Hash::make('existing-password');
            DB::table('employees')->insert([
                ['employee_id' => 4, 'role' => 'admin', 'name' => 'Admin', 'username' => 'admin',
                    'password' => $password, 'remember_token' => 'admin-token', 'created_by' => null, 'is_active' => true],
                ['employee_id' => 15, 'role' => 'staff', 'name' => 'Staff', 'username' => 'staff',
                    'password' => $password, 'remember_token' => 'staff-token', 'created_by' => 4, 'is_active' => false],
            ]);
            DB::table('bookings')->insert(['staff_id' => 15, 'reschedule_approved_by_admin_id' => 4]);

            $migration = require database_path('migrations/2026_10_29_000001_separate_admin_and_staff_tables.php');
            $migration->up();

            $this->assertFalse(Schema::hasTable('employees'));
            $this->assertDatabaseHas('admins', ['admin_id' => 4, 'password' => $password, 'remember_token' => 'admin-token']);
            $this->assertDatabaseHas('staff', ['staff_id' => 15, 'password' => $password,
                'remember_token' => 'staff-token', 'created_by' => 4, 'is_active' => false]);
            $this->assertDatabaseHas('bookings', ['staff_id' => 15, 'reschedule_approved_by_admin_id' => 4]);
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));

            DB::table('admins')->where('admin_id', 4)->delete();
            $this->assertDatabaseHas('staff', ['staff_id' => 15, 'created_by' => null]);
            $this->assertDatabaseHas('bookings', ['staff_id' => 15, 'reschedule_approved_by_admin_id' => null]);
            DB::table('staff')->where('staff_id', 15)->delete();
            $this->assertDatabaseHas('bookings', ['staff_id' => null]);
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('account_split_test');
        }
    }
}
