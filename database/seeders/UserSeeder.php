<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = (string) env('SEED_DEFAULT_PASSWORD', 'password');

        Admin::updateOrCreate(
            ['username' => strtolower(trim((string) env('SEED_ADMIN_USERNAME', 'admin')))],
            [
                'name' => (string) env('SEED_ADMIN_NAME', 'Admin User'),
                'phone' => (string) env('SEED_ADMIN_PHONE', '09170000001'),
                'password' => Hash::make((string) env('SEED_ADMIN_PASSWORD', $defaultPassword)),
            ]
        );
    }
}
