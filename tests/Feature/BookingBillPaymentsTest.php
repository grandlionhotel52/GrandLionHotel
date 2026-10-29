<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Services\PaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingBillPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_collections_keep_distinct_receipts_and_a_computed_balance(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);
        $booking->bill()->update(['total_amount' => 1000]);
        $service = app(PaymentService::class);

        $first = $service->charge($booking, 'cash', ['amount' => 400]);
        $firstReference = $first->transaction_reference;
        $this->assertSame('400.00', $first->amount);
        $this->assertSame('paid', $first->status);
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
        $this->assertSame(600.0, $booking->bill()->firstOrFail()->outstandingAmount());

        $second = $service->charge($booking, 'cash');
        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($firstReference, $second->transaction_reference);
        $this->assertSame('600.00', $second->amount);
        $this->assertSame('400.00', $first->fresh()->amount);
        $this->assertSame($firstReference, $first->fresh()->transaction_reference);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(0.0, $booking->bill()->firstOrFail()->outstandingAmount());
        $this->assertSame(1000.0, (float) $booking->payments()->where('status', 'paid')->sum('amount'));
        $this->assertSame($second->id, $service->charge($booking)->id);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_additional_charge_keeps_previous_payment_and_provider_replay_does_not_collect_twice(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);
        $booking->bill()->update(['total_amount' => 1000]);
        $service = app(PaymentService::class);
        $original = $service->charge($booking, 'gcash', ['provider_payment_id' => 'pay_original']);
        $originalAttributes = $original->getAttributes();

        $booking->bill()->update(['total_amount' => 1250]);
        $attempt = $booking->preparePaymentAttempt(['amount' => 250, 'method' => 'cash', 'status' => 'unpaid']);
        $this->assertNotSame($original->id, $attempt->id);
        $this->assertSame('unpaid', $booking->fresh()->payment_status);
        $this->assertSame($original->id, $service->charge($booking, 'gcash', ['provider_payment_id' => 'pay_original'])->id);
        $this->assertSame(250.0, $booking->bill()->firstOrFail()->outstandingAmount());

        $additional = $service->charge($booking, 'cash');
        $this->assertSame('250.00', $additional->amount);
        $this->assertSame($originalAttributes, $original->fresh()->getAttributes());
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(1250.0, $booking->fresh()->total_price);
    }

    public function test_sales_report_counts_collections_without_duplicating_bookings_or_discounts(): void
    {
        $admin = Admin::factory()->create();
        $booking = Booking::factory()->create(['status' => 'pending']);
        $booking->bill()->update(['total_amount' => 1000, 'discount_amount' => 100]);
        $booking->details()->update(['meal_plan' => 'breakfast_included']);
        config(['pricing.breakfast_fee' => 200]);
        $service = app(PaymentService::class);
        $first = $service->charge($booking, 'cash', ['amount' => 400]);

        $query = ['from' => today()->toDateString(), 'to' => today()->toDateString()];
        $this->actingAs($admin, 'admin')->get(route('admin.sales-report', $query))
            ->assertOk()->assertViewHas('summary', fn ($summary) => $summary['gross_revenue'] === 400.0
                && $summary['total_discount'] === 40.0 && $summary['paid_bookings'] === 1);
        // A real collection has a receipt even while the bill still has a balance.
        $this->get(route('admin.sales-report.receipt', $first))->assertOk();
        $this->actingAs($booking->customer, 'customer')->get(route('bookings.receipt', $booking))->assertOk();

        $service->charge($booking, 'cash');
        $this->actingAs($admin, 'admin')->get(route('admin.sales-report', $query))
            ->assertOk()->assertViewHas('summary', fn ($summary) => $summary['gross_revenue'] === 1000.0
                && $summary['total_discount'] === 100.0 && $summary['food_sales'] === 200.0
                && $summary['paid_bookings'] === 1);
    }

    public function test_bill_fields_and_fixed_capacity_are_not_duplicated_in_transaction_or_room_storage(): void
    {
        foreach (['balance_due', 'original_amount', 'discount_rate', 'discount_amount'] as $column) {
            $this->assertFalse(Schema::hasColumn('payments', $column));
        }
        $this->assertFalse(Schema::hasColumn('payments', 'booking_id'));
        $this->assertTrue(Schema::hasColumn('payments', 'booking_bill_id'));
        $this->assertFalse(Schema::hasColumn('rooms', 'capacity'));
        $this->assertSame(2, Room::factory()->create(['capacity' => 5])->capacity);
    }

    public function test_capturing_an_older_checkout_supersedes_other_attempts_and_keeps_the_correct_receipt(): void
    {
        $booking = Booking::factory()->create(['status' => 'pending']);
        $booking->bill()->update(['total_amount' => 1000]);
        $older = $booking->payment;
        $older->update(['amount' => 1000, 'provider_session_id' => 'cs_older']);
        $newer = $booking->createPayment([
            'amount' => 1000, 'status' => 'unpaid', 'method' => 'gcash', 'provider_session_id' => 'cs_newer',
        ]);

        $paid = app(PaymentService::class)->charge($booking, 'gcash', [
            'provider_session_id' => 'cs_older', 'provider_payment_id' => 'pay_older',
        ]);

        $this->assertSame($older->id, $paid->id);
        $this->assertSame('cancelled', $newer->fresh()->status);
        $this->assertSame($paid->id, $booking->fresh()->payment->id);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame(1, $booking->payments()->where('status', 'paid')->count());
    }

    public function test_populated_legacy_migration_preserves_paid_records_and_open_balances(): void
    {
        $originalConnection = DB::getDefaultConnection();
        config(['database.connections.legacy_billing_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('legacy_billing_test');
        try {
            Schema::create('bookings', fn (Blueprint $table) => $table->bigIncrements('booking_id'));
            Schema::create('rooms', function (Blueprint $table): void {
                $table->id();
                $table->integer('capacity')->default(2);
            });
            Schema::create('payments', function (Blueprint $table): void {
                $table->bigIncrements('payment_id');
                $table->unsignedBigInteger('booking_id')->unique('payments_booking_id_unique');
                $table->foreign('booking_id')->references('booking_id')->on('bookings');
                foreach (['amount', 'balance_due', 'original_amount', 'discount_rate', 'discount_amount'] as $column) {
                    $table->decimal($column, 12, 4)->nullable();
                }
                $table->string('status');
                $table->string('transaction_reference')->nullable();
                $table->string('provider_payment_id')->nullable();
                foreach (['provider_session_id', 'method', 'source', 'qr_reference', 'customer_reference', 'payment_proof_path'] as $column) {
                    $table->string($column)->nullable();
                }
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
            Schema::create('activity_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('actor_type')->nullable();
                $table->integer('actor_id')->nullable();
                $table->string('action');
                $table->string('subject_type');
                $table->integer('subject_id');
                $table->json('changes');
                $table->timestamps();
            });
            DB::table('bookings')->insert([['booking_id' => 1], ['booking_id' => 2], ['booking_id' => 3]]);
            DB::table('payments')->insert([
                ['booking_id' => 1, 'amount' => 1000, 'balance_due' => 0, 'status' => 'paid', 'paid_at' => '2026-10-01 09:00:00', 'transaction_reference' => 'PAID-1'],
                ['booking_id' => 2, 'amount' => 1250, 'balance_due' => 250, 'status' => 'unpaid', 'paid_at' => '2026-10-01 09:00:00', 'transaction_reference' => 'OLD-2'],
                ['booking_id' => 3, 'amount' => 400, 'balance_due' => 600, 'status' => 'unpaid', 'paid_at' => null, 'transaction_reference' => 'PARTIAL-3'],
            ]);
            $migration = require database_path('migrations/2026_10_30_000002_separate_booking_bills_and_payment_transactions.php');
            $migration->up();

            $this->assertDatabaseHas('booking_bills', ['booking_id' => 1, 'total_amount' => 1000, 'opening_credit_amount' => 0]);
            $this->assertDatabaseHas('payments', ['booking_id' => 1, 'amount' => 1000, 'status' => 'paid', 'transaction_reference' => 'PAID-1', 'paid_at' => '2026-10-01 09:00:00']);
            $this->assertDatabaseHas('booking_bills', ['booking_id' => 2, 'total_amount' => 1250, 'opening_credit_amount' => 0]);
            $this->assertDatabaseHas('payments', ['booking_id' => 2, 'amount' => 1000, 'status' => 'paid', 'transaction_reference' => 'OLD-2']);
            $this->assertDatabaseHas('booking_bills', ['booking_id' => 3, 'total_amount' => 1000, 'opening_credit_amount' => 400]);
            $this->assertDatabaseHas('payments', ['booking_id' => 2, 'amount' => 250, 'status' => 'unpaid']);
            $this->assertDatabaseCount('activity_logs', 2);
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
            $rekey = require database_path('migrations/2026_10_30_000003_use_booking_bill_identifiers_on_payments.php');
            $rekey->up();
            $this->assertDatabaseHas('booking_bills', ['booking_bill_id' => 2, 'booking_id' => 2]);
            $this->assertDatabaseHas('payments', ['booking_bill_id' => 2, 'amount' => 1000, 'status' => 'paid']);
            $this->assertFalse(Schema::hasColumn('payments', 'booking_id'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('legacy_billing_test');
        }
    }
}
