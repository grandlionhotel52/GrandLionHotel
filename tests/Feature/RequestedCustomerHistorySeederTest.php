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
        'Clarissa Domingo', 'Dominic Villanueva', 'Emmanuel Garcia', 'Francesca Cruz',
        'Nathaniel Ramos', 'Sophia Mendoza',
    ];

    public function test_requested_customer_history_is_seeded_idempotently(): void
    {
        $this->seed();

        $seededCustomerIds = DB::table('customers')
            ->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-18 23:59:59'])
            ->pluck('customer_id');
        $customerCount = $seededCustomerIds->count();
        $bookingCount = DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count();

        $this->assertSame(80, $customerCount);
        foreach (range(14, 18) as $day) {
            $this->assertSame(16, DB::table('customers')->whereDate('created_at', "2026-09-{$day}")->count());
        }
        $this->assertGreaterThan(0, $bookingCount);
        $this->assertTrue(DB::table('customers')->where('name', 'Maurice Valdez')->exists());
        $this->assertTrue(DB::table('customers')->where('name', 'Jazmine Ramos')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('name', 'like', '% Gew')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('email', 'like', '%.demo@%')->exists());

        $additionalCustomers = DB::table('customers')
            ->whereIn('name', self::ADDITIONAL_NAMES)
            ->get(['customer_id', 'phone']);
        $additionalCustomerIds = $additionalCustomers->pluck('customer_id');

        $this->assertCount(34, $additionalCustomers);
        $this->assertSame(34, $additionalCustomers->pluck('phone')->unique()->count());
        $this->assertFalse($additionalCustomers->contains(
            fn (object $customer): bool => str_starts_with((string) $customer->phone, '091700010')
        ));
        $this->assertSame(
            34,
            DB::table('bookings')->whereIn('customer_id', $additionalCustomerIds)->distinct()->count('customer_id')
        );
        $this->assertSame(
            0,
            DB::table('bookings')
                ->whereIn('customer_id', $additionalCustomerIds)
                ->whereNotBetween('created_at', ['2026-09-14 00:00:00', '2026-09-18 23:59:59'])
                ->count()
        );

        $this->assertSame(
            80,
            DB::table('activity_logs')
                ->where('action', 'logged_in')
                ->where('subject_type', 'Customer')
                ->whereIn('subject_id', $seededCustomerIds)
                ->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-18 23:59:59'])
                ->distinct()
                ->count('subject_id')
        );

        $this->assertSame(
            0,
            DB::table('bookings as first_booking')
                ->join('bookings as second_booking', function ($join): void {
                    $join->on('first_booking.room_id', '=', 'second_booking.room_id')
                        ->on('first_booking.booking_id', '<', 'second_booking.booking_id')
                        ->on('first_booking.check_in', '<', 'second_booking.check_out')
                        ->on('first_booking.check_out', '>', 'second_booking.check_in');
                })
                ->whereIn('first_booking.customer_id', $seededCustomerIds)
                ->whereIn('second_booking.customer_id', $seededCustomerIds)
                ->count()
        );

        $this->seed(RequestedCustomerHistorySeeder::class);

        $this->assertSame($customerCount, DB::table('customers')->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-18 23:59:59'])->count());
        $this->assertSame($bookingCount, DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count());
    }

    public function test_customer_accounts_and_logins_cover_september_14_through_18(): void
    {
        $this->seed();

        $this->assertSame(80, Customer::query()
            ->whereBetween('created_at', ['2026-09-14 00:00:00', '2026-09-18 23:59:59'])
            ->count());
        $this->assertSame(16, Customer::query()->whereDate('created_at', '2026-09-18')->count());
    }
}
