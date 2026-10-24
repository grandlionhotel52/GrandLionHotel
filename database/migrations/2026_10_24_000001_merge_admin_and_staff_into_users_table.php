<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->bigIncrements('user_id');
            $table->string('role', 20)->index();
            $table->string('name');
            $table->string('username', 50)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password');
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['role', 'is_active']);
        });

        $adminIds = DB::table('admins')->orderBy('admin_id')->pluck('admin_id');
        $staffOffset = (int) ($adminIds->max() ?? 0);

        foreach (DB::table('admins')->orderBy('admin_id')->get() as $admin) {
            DB::table('users')->insert([
                'user_id' => (int) $admin->admin_id,
                'role' => 'admin',
                'name' => $admin->name,
                'username' => $admin->username,
                'phone' => $admin->phone,
                'password' => $admin->password,
                'password_changed_at' => $admin->password_changed_at,
                'remember_token' => $admin->remember_token,
                'created_by' => null,
                'is_active' => true,
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]);
        }

        $staffIdMap = [];
        foreach (DB::table('staff')->orderBy('staff_id')->get() as $staff) {
            $newId = $staffOffset + (int) $staff->staff_id;
            $staffIdMap[(int) $staff->staff_id] = $newId;

            DB::table('users')->insert([
                'user_id' => $newId,
                'role' => 'staff',
                'name' => $staff->name,
                'username' => $staff->username,
                'phone' => $staff->phone,
                'password' => $staff->password,
                'password_changed_at' => $staff->password_changed_at,
                'remember_token' => $staff->remember_token,
                'created_by' => $staff->admin_id,
                'is_active' => $staff->is_active ?? true,
                'created_at' => $staff->created_at,
                'updated_at' => $staff->updated_at,
            ]);
        }

        Schema::disableForeignKeyConstraints();

        $this->moveReference('rooms', 'admin_id', []);
        $this->moveReference('bookings', 'staff_id', $staffIdMap);
        $this->moveReference('bookings', 'reschedule_approved_by_admin_id', []);
        $this->moveReference('booking_extra_bedding_requests', 'responded_by_staff_id', $staffIdMap);

        // These columns exist only in some upgraded installations.
        $this->moveReference('payments', 'staff_id', $staffIdMap);
        $this->moveReference('booking_guest_details', 'staff_id', $staffIdMap);
        $this->moveReference('room_date_discounts', 'admin_id', []);

        $this->remapPolymorphicIds('activity_logs', 'actor_type', 'actor_id', $staffIdMap);
        $this->remapPolymorphicIds('activity_logs', 'subject_type', 'subject_id', $staffIdMap);
        $this->remapPolymorphicIds('notifications', 'notifiable_type', 'notifiable_id', $staffIdMap);

        Schema::dropIfExists('staff');
        Schema::dropIfExists('admins');

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('created_by', 'users_created_by_users_fk')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });

        $this->createLegacyReadViews();

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        throw new RuntimeException('Rollback is not supported after merging admin and staff accounts.');
    }

    /**
     * Replace a role-specific foreign key with one pointing at users.user_id.
     *
     * @param  array<int, int>  $idMap
     */
    private function moveReference(string $tableName, string $columnName, array $idMap): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        $temporaryColumn = $columnName.'_user_ref';

        Schema::table($tableName, function (Blueprint $table) use ($temporaryColumn, $columnName): void {
            $table->unsignedBigInteger($temporaryColumn)->nullable()->after($columnName);
        });

        DB::table($tableName)
            ->whereNotNull($columnName)
            ->orderBy($columnName)
            ->get([$columnName])
            ->pluck($columnName)
            ->unique()
            ->each(function ($oldId) use ($tableName, $columnName, $temporaryColumn, $idMap): void {
                $oldId = (int) $oldId;
                $newId = $idMap[$oldId] ?? $oldId;

                DB::table($tableName)
                    ->where($columnName, $oldId)
                    ->update([$temporaryColumn => $newId]);
            });

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
                $table->dropForeign([$columnName]);
            });
        } catch (Throwable) {
            // The column may be unconstrained in a partially upgraded database.
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
            $table->dropColumn($columnName);
        });

        Schema::table($tableName, function (Blueprint $table) use ($temporaryColumn, $columnName): void {
            $table->renameColumn($temporaryColumn, $columnName);
        });

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $columnName): void {
            $foreignName = substr($tableName.'_'.$columnName.'_users_fk', 0, 63);
            $table->foreign($columnName, $foreignName)
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * @param  array<int, int>  $staffIdMap
     */
    private function remapPolymorphicIds(
        string $tableName,
        string $typeColumn,
        string $idColumn,
        array $staffIdMap
    ): void {
        if (! Schema::hasTable($tableName)
            || ! Schema::hasColumn($tableName, $typeColumn)
            || ! Schema::hasColumn($tableName, $idColumn)) {
            return;
        }

        $staffTypes = ['Staff', 'App\\Models\\Staff'];
        foreach ($staffIdMap as $oldId => $newId) {
            DB::table($tableName)
                ->whereIn($typeColumn, $staffTypes)
                ->where($idColumn, $oldId)
                ->update([$idColumn => $newId]);
        }
    }

    /**
     * Keep old reports and integrations readable without duplicating account data.
     */
    private function createLegacyReadViews(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIEW admins AS
            SELECT
                user_id AS admin_id,
                name,
                username,
                password,
                phone,
                password_changed_at,
                remember_token,
                created_at,
                updated_at
            FROM users
            WHERE role = 'admin'
            SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW staff AS
            SELECT
                user_id AS staff_id,
                name,
                username,
                password,
                phone,
                password_changed_at,
                remember_token,
                created_by AS admin_id,
                is_active,
                created_at,
                updated_at
            FROM users
            WHERE role = 'staff'
            SQL);
    }
};
