<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestedCustomerPanelCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_requested_customer_is_removed_and_remaining_joined_dates_move_to_september_15(): void
    {
        $emails = [
            'justinnamoro8@gmail.com',
            'ludwigjvdevera@gmail.com',
            'castromarkwill76@gmail.com',
            'nakiriayame75@gmail.com',
            'aquinoroland439@gmail.com',
            'ariannekatefernandez@gmail.com',
        ];

        foreach ($emails as $index => $email) {
            Customer::factory()->create([
                'email' => $email,
                'email_verified_at' => "2026-09-2{$index} 09:0{$index}:00",
                'created_at' => "2026-09-2{$index} 09:0{$index}:00",
                'updated_at' => "2026-09-2{$index} 09:0{$index}:00",
            ]);
        }

        $removedCustomer = Customer::factory()->create([
            'email' => 'sheilamaezing.24@gmail.com',
        ]);
        $removedBooking = Booking::factory()->create([
            'customer_id' => $removedCustomer->customer_id,
        ]);

        $migration = require database_path('migrations/2026_09_30_000001_correct_requested_customer_panel_records.php');
        $migration->up();

        $this->assertDatabaseMissing('customers', ['customer_id' => $removedCustomer->customer_id]);
        $this->assertDatabaseMissing('bookings', ['booking_id' => $removedBooking->booking_id]);
        $this->assertSame(
            6,
            DB::table('customers')
                ->whereIn('email', $emails)
                ->whereDate('created_at', '2026-09-15')
                ->whereDate('email_verified_at', '2026-09-15')
                ->count()
        );
    }
}
