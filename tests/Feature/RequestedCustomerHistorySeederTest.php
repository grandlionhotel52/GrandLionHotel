<?php

namespace Tests\Feature;

use App\Models\Customer;
use Database\Seeders\RequestedCustomerHistorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestedCustomerHistorySeederTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_DATES = [
        '2026-10-07' => 12,
        '2026-10-08' => 12,
        '2026-10-09' => 12,
        '2026-10-12' => 11,
        '2026-10-13' => 11,
        '2026-10-14' => 11,
        '2026-10-15' => 11,
    ];

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
            ->whereBetween('created_at', ['2026-10-07 00:00:00', '2026-10-15 23:59:59'])
            ->pluck('customer_id');
        $customerCount = $seededCustomerIds->count();
        $bookingCount = DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count();

        $this->assertSame(80, $customerCount);
        foreach (self::LOGIN_DATES as $date => $expectedCount) {
            $this->assertSame($expectedCount, DB::table('customers')->whereDate('created_at', $date)->count());
        }
        $this->assertSame(0, DB::table('customers')->whereDate('created_at', '2026-10-10')->count());
        $this->assertSame(0, DB::table('customers')->whereDate('created_at', '2026-10-11')->count());
        $this->assertSame(0, DB::table('customers')->whereMonth('created_at', 9)->whereYear('created_at', 2026)->count());
        $this->assertGreaterThan(0, $bookingCount);
        $this->assertTrue(DB::table('customers')->where('name', 'Maurice Valdez')->exists());
        $this->assertTrue(DB::table('customers')->where('name', 'Jazmine Ramos')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('name', 'like', '% Gew')->exists());
        $this->assertFalse(DB::table('customers')->whereIn('customer_id', $seededCustomerIds)->where('email', 'like', '%.demo@%')->exists());
        $seededCustomers = DB::table('customers')
            ->whereIn('customer_id', $seededCustomerIds)
            ->get(['address_line', 'city', 'province', 'country']);
        $this->assertSame(
            ['Calasiao', 'Malasiqui', 'San Carlos City'],
            $seededCustomers->pluck('city')->unique()->sort()->values()->all()
        );
        $this->assertFalse($seededCustomers->contains(
            fn (object $customer): bool => blank($customer->address_line)
                || $customer->province !== 'Pangasinan'
                || $customer->country !== 'Philippines'
        ));

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
                ->whereNotBetween('created_at', ['2026-10-01 00:00:00', '2026-12-31 23:59:59'])
                ->count()
        );

        $this->assertSame(
            80,
            DB::table('activity_logs')
                ->where('action', 'logged_in')
                ->where('subject_type', 'Customer')
                ->whereIn('subject_id', $seededCustomerIds)
                ->whereBetween('created_at', ['2026-10-07 00:00:00', '2026-10-15 23:59:59'])
                ->distinct()
                ->count('subject_id')
        );

        $seededBookings = DB::table('bookings')
            ->whereIn('customer_id', $seededCustomerIds)
            ->get(['created_at', 'check_in', 'check_out']);
        $this->assertSame(
            ['10', '11', '12'],
            $seededBookings->pluck('created_at')->map(
                fn (string $date): string => Carbon::parse($date)->format('m')
            )->unique()->sort()->values()->all()
        );
        $this->assertSame(
            ['10', '11', '12'],
            $seededBookings->pluck('check_in')->map(
                fn (string $date): string => Carbon::parse($date)->format('m')
            )->unique()->sort()->values()->all()
        );
        $this->assertFalse($seededBookings->contains(
            fn (object $booking): bool => Carbon::parse($booking->created_at)->year !== 2026
                || Carbon::parse($booking->check_in)->year !== 2026
                || Carbon::parse($booking->check_out)->year !== 2026
                || Carbon::parse($booking->created_at)->month < 10
                || Carbon::parse($booking->check_in)->month < 10
                || Carbon::parse($booking->check_out)->month < 10
        ));

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

        $this->assertSame($customerCount, DB::table('customers')->whereBetween('created_at', ['2026-10-07 00:00:00', '2026-10-15 23:59:59'])->count());
        $this->assertSame($bookingCount, DB::table('bookings')->whereIn('customer_id', $seededCustomerIds)->count());
    }

    public function test_customer_accounts_and_logins_use_only_the_requested_october_dates(): void
    {
        $this->seed();

        $this->assertSame(80, Customer::query()
            ->whereBetween('created_at', ['2026-10-07 00:00:00', '2026-10-15 23:59:59'])
            ->count());
        $this->assertSame(0, Customer::query()->whereDate('created_at', '2026-10-10')->count());
        $this->assertSame(0, Customer::query()->whereDate('created_at', '2026-10-11')->count());
    }

    public function test_existing_september_history_is_moved_to_the_new_dates(): void
    {
        $this->seed();

        $customerIds = Customer::query()->pluck('customer_id');
        $bookingCount = DB::table('bookings')->whereIn('customer_id', $customerIds)->count();

        Customer::query()->update([
            'address_line' => 'Old Address',
            'city' => 'Dagupan City',
            'province' => 'Pangasinan',
            'created_at' => '2026-09-14 08:00:00',
            'updated_at' => '2026-09-14 08:05:00',
        ]);
        DB::table('activity_logs')->whereIn('subject_id', $customerIds)->update([
            'created_at' => '2026-09-14 08:12:00',
            'updated_at' => '2026-09-14 08:12:00',
        ]);
        DB::table('bookings')->whereIn('customer_id', $customerIds)->update([
            'created_at' => '2026-09-15 09:00:00',
            'updated_at' => '2026-09-15 10:00:00',
            'check_in' => '2026-09-20',
            'check_out' => '2026-09-22',
        ]);

        $this->seed(RequestedCustomerHistorySeeder::class);

        $this->assertSame(0, Customer::query()->whereMonth('created_at', 9)->whereYear('created_at', 2026)->count());
        $this->assertSame(0, Customer::query()->whereNotIn('city', ['San Carlos City', 'Calasiao', 'Malasiqui'])->count());
        $this->assertSame($bookingCount, DB::table('bookings')->whereIn('customer_id', $customerIds)->count());
        $this->assertSame(0, DB::table('bookings')->whereIn('customer_id', $customerIds)->whereMonth('created_at', 9)->count());
        $this->assertSame(0, DB::table('bookings')->whereIn('customer_id', $customerIds)->whereMonth('check_in', 9)->count());
    }
}
