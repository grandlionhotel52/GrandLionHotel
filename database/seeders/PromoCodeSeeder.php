<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::query()->updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'description' => 'Welcome offer for your first hotel booking.',
                'discount_percent' => 30,
                'starts_at' => null,
                'ends_at' => null,
                'new_customers_only' => true,
                'is_active' => true,
            ]
        );
    }
}
