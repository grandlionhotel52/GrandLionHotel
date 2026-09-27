<?php

namespace Tests\Feature;

use Database\Seeders\RequestedCustomerHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestedCustomerHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_requested_customer_history_is_seeded_idempotently(): void
    {
        $this->seed();

        $seededCustomerIds = DB::table('customers')
            ->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-18 23:59:59'])
            ->pluck('customer_id');
        $customerCount = $seededCustomerIds->count();
        $bookingCount = DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count();

        $this->assertSame(46, $customerCount);
        $this->assertGreaterThan(0, $bookingCount);
        $this->assertTrue(DB::table('customers')->where('name', 'Maurice Valdez')->exists());
        $this->assertTrue(DB::table('customers')->where('name', 'Jazmine Ramos')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('name', 'like', '% Gew')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('email', 'like', '%.demo@%')->exists());

        $this->seed(RequestedCustomerHistorySeeder::class);

        $this->assertSame($customerCount, DB::table('customers')->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-18 23:59:59'])->count());
        $this->assertSame($bookingCount, DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count());
    }
}
