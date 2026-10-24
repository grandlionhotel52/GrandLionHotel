<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS staff');
        DB::statement('DROP VIEW IF EXISTS admins');
    }

    public function down(): void
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
            FROM employees
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
            FROM employees
            WHERE role = 'staff'
            SQL);
    }
};
