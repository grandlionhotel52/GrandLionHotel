<?php

namespace Tests\Feature;

use App\Models\Customer;
use Database\Seeders\RequestedCustomerHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestedCustomerHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    private const ADDITIONAL_NAMES = [
        'Andrea Villareal', 'Paolo Mendoza', 'Bianca Torres', 'Carlo Ramirez',
        'Denise Santiago', 'Elijah Fernandez', 'Faith Gonzales', 'Gabriel Lim',
        'Hannah Bautista', 'Ivan Castillo', 'Julia Navarro', 'Kevin Aquino',
        'Lara Dominguez', 'Miguel Pascual', 'Nicole Salazar', 'Oscar Rivera',
        'Patricia Reyes', 'Rafael Soriano', 'Samantha Valdez', 'Tristan Mercado',
        'Uma Cabrera', 'Victor Manalo', 'Wendy Padilla', 'Xavier Flores',
        'Yvonne Dizon', 'Zachary Alonzo', 'Alyssa Evangelista', 'Brandon Macaraeg',
        'Clarissa Domingo', 'Dominic Villanueva',
    ];

    public function test_requested_customer_history_is_seeded_idempotently(): void
    {
        $this->seed();

        $seededCustomerIds = DB::table('customers')
            ->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-17 23:59:59'])
            ->pluck('customer_id');
        $customerCount = $seededCustomerIds->count();
        $bookingCount = DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count();

        $this->assertSame(76, $customerCount);
        $this->assertSame(0, DB::table('customers')->whereDate('created_at', '2026-09-18')->count());
        $this->assertGreaterThan(0, $bookingCount);
        $this->assertTrue(DB::table('customers')->where('name', 'Maurice Valdez')->exists());
        $this->assertTrue(DB::table('customers')->where('name', 'Jazmine Ramos')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('name', 'like', '% Gew')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('email', 'like', '%.demo@%')->exists());

        $additionalCustomers = DB::table('customers')
            ->whereIn('name', self::ADDITIONAL_NAMES)
            ->get(['customer_id', 'phone']);
        $additionalCustomerIds = $additionalCustomers->pluck('customer_id');

        $this->assertCount(30, $additionalCustomers);
        $this->assertSame(30, $additionalCustomers->pluck('phone')->unique()->count());
        $this->assertFalse($additionalCustomers->contains(
            fn (object $customer): bool => str_starts_with((string) $customer->phone, '091700010')
        ));
        $this->assertSame(
            30,
            DB::table('bookings')->whereIn('customer_id', $additionalCustomerIds)->distinct()->count('customer_id')
        );
        $this->assertSame(
            0,
            DB::table('bookings')
                ->whereIn('customer_id', $additionalCustomerIds)
                ->whereNotBetween('created_at', ['2026-09-14 00:00:00', '2026-09-17 23:59:59'])
                ->count()
        );

        $this->seed(RequestedCustomerHistorySeeder::class);

        $this->assertSame($customerCount, DB::table('customers')->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-17 23:59:59'])->count());
        $this->assertSame($bookingCount, DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count());
    }

    public function test_every_september_18_customer_is_redistributed_to_september_14_through_17(): void
    {
        $this->seed();

        $customer = Customer::factory()->create([
            'created_at' => '2026-09-18 15:42:19',
            'updated_at' => '2026-09-18 15:42:19',
        ]);

        $this->seed(RequestedCustomerHistorySeeder::class);

        $customer->refresh();

        $this->assertTrue($customer->created_at->between(
            '2026-09-14 00:00:00',
            '2026-09-17 23:59:59'
        ));
        $this->assertSame(0, DB::table('customers')->whereDate('created_at', '2026-09-18')->count());
    }
}
