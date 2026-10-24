<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropLegacyReadViews();
        Schema::rename('users', 'employees');
        $this->createLegacyReadViews('employees');
    }

    public function down(): void
    {
        $this->dropLegacyReadViews();
        Schema::rename('employees', 'users');
        $this->createLegacyReadViews('users');
    }

    private function dropLegacyReadViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS staff');
        DB::statement('DROP VIEW IF EXISTS admins');
    }

    private function createLegacyReadViews(string $accountTable): void
    {
        DB::statement(<<<SQL
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
            FROM {$accountTable}
            WHERE role = 'admin'
            SQL);

        DB::statement(<<<SQL
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
            FROM {$accountTable}
            WHERE role = 'staff'
            SQL);
    }
};
