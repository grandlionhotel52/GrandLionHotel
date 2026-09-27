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

        $customerCount = DB::table('customers')
            ->where('email', 'like', '%.demo@gmail.com')
            ->count();
        $bookingCount = DB::table('bookings')
            ->where('notes', 'like', 'Requested demo history:%')
            ->count();

        $this->assertSame(46, $customerCount);
        $this->assertGreaterThan(0, $bookingCount);
        $this->assertTrue(DB::table('customers')->where('name', 'Maurice Gew')->exists());
        $this->assertTrue(DB::table('customers')->where('name', 'Jazmine Gew')->exists());

        $this->seed(RequestedCustomerHistorySeeder::class);

        $this->assertSame($customerCount, DB::table('customers')->where('email', 'like', '%.demo@gmail.com')->count());
        $this->assertSame($bookingCount, DB::table('bookings')->where('notes', 'like', 'Requested demo history:%')->count());
    }
}
