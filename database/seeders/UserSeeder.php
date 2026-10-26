<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $manageAdminOnDeploy = filter_var(
            env('SEED_ADMIN_ON_DEPLOY', false),
            FILTER_VALIDATE_BOOL
        );

        // A fresh production database needs one account that can administer it.
        // Once an administrator exists, only update it when explicitly requested.
        if (Admin::query()->exists() && !$manageAdminOnDeploy) {
            return;
        }

        $defaultPassword = (string) env('SEED_DEFAULT_PASSWORD', 'Password');

        Admin::updateOrCreate(
            ['username' => strtolower(trim((string) env('SEED_ADMIN_USERNAME', 'admin')))],
            [
                'role' => 'admin',
                'name' => (string) env('SEED_ADMIN_NAME', 'Admin User'),
                'phone' => (string) env('SEED_ADMIN_PHONE', '09170000001'),
                'password' => Hash::make((string) env('SEED_ADMIN_PASSWORD', $defaultPassword)),
                'is_active' => true,
            ]
        );
    }
}
