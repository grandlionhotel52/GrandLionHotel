<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $promo = PromoCode::query()
            ->whereIn('code', ['NEWUSERDISCOUNT', 'WELCOME10'])
            ->first() ?? new PromoCode();

        $promo->fill([
            'code' => 'NEWUSERDISCOUNT',
            'description' => 'New User Discount for your first hotel booking.',
            'discount_percent' => 30,
            'starts_at' => null,
            'ends_at' => null,
            'new_customers_only' => true,
            'is_active' => true,
        ])->save();
    }
}
