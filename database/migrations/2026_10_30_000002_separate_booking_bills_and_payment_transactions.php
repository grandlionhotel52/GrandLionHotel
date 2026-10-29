<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A bill shares its booking's key: payments need only one reference,
        // rather than storing both a bill ID and its dependent booking ID.
        Schema::create('booking_bills', function (Blueprint $table): void {
            $table->unsignedBigInteger('booking_id')->primary();
            $table->decimal('total_amount', 12, 2);
            $table->decimal('original_amount', 12, 2)->nullable();
            $table->decimal('discount_rate', 8, 4)->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            // One-time cutover credit where the old mutable row no longer
            // contains the individual collections. Never count this as a new sale.
            $table->decimal('opening_credit_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->cascadeOnDelete();
        });

        $pendingPayments = [];
        DB::table('payments')->orderBy('payment_id')->chunkById(200, function ($payments) use (&$pendingPayments): void {
            foreach ($payments as $payment) {
                $amount = round((float) $payment->amount, 2);
                $balance = round(max(0, (float) $payment->balance_due), 2);
                $total = $amount;
                $credit = 0;
                if ($payment->status !== 'paid' && $balance > 0) {
                    // A paid_at value survives a reopened reschedule bill; a
                    // partial initial collection historically cleared paid_at.
                    $total = $payment->paid_at ? max($amount, $balance) : $amount + $balance;
                    $credit = round($total - $balance, 2);
                    DB::table('activity_logs')->insert([
                        'actor_type' => null, 'actor_id' => null,
                        'action' => 'legacy_payment_balance_migrated',
                        'subject_type' => 'Payment', 'subject_id' => $payment->payment_id,
                        'changes' => json_encode(['before' => (array) $payment, 'after' => [
                            'opening_credit_amount' => $payment->paid_at && $credit > 0 ? 0 : $credit,
                            'preserved_collection_amount' => $payment->paid_at ? $credit : null,
                            'outstanding_amount' => $balance,
                            'history' => 'Legacy aggregate; individual previous collections are unavailable.',
                        ]], JSON_THROW_ON_ERROR),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($payment->paid_at && $credit > 0) {
                        // The old reschedule path retained the original receipt
                        // metadata. Preserve that collection and create a new
                        // attempt for the added amount instead of replacing it.
                        DB::table('payments')->where('payment_id', $payment->payment_id)->update([
                            'amount' => $credit, 'status' => 'paid',
                        ]);
                        $pendingPayments[] = [
                            'booking_id' => $payment->booking_id, 'amount' => $balance,
                            'status' => 'unpaid', 'method' => 'pending',
                            'source' => 'legacy_balance_pending',
                            'created_at' => $payment->updated_at, 'updated_at' => $payment->updated_at,
                        ];
                        $credit = 0;
                    } else {
                        DB::table('payments')->where('payment_id', $payment->payment_id)->update([
                            'amount' => $balance, 'paid_at' => null,
                            'transaction_reference' => null, 'provider_payment_id' => null,
                            'provider_session_id' => null, 'method' => 'pending',
                            'source' => 'legacy_balance_pending',
                            'qr_reference' => null, 'customer_reference' => null,
                            'payment_proof_path' => null, 'verified_at' => null,
                            'status' => 'unpaid',
                        ]);
                    }
                }
                DB::table('booking_bills')->insert([
                    'booking_id' => $payment->booking_id, 'total_amount' => $total,
                    'original_amount' => $payment->original_amount,
                    'discount_rate' => $payment->discount_rate,
                    'discount_amount' => $payment->discount_amount,
                    'opening_credit_amount' => $credit,
                    'created_at' => $payment->created_at, 'updated_at' => $payment->updated_at,
                ]);
            }
        }, 'payment_id');

        foreach (Schema::getForeignKeys('payments') as $foreign) {
            if ($foreign['columns'] === ['booking_id']) {
                Schema::table('payments', fn (Blueprint $table) => $table->dropForeign($foreign['name'] ?? $foreign['columns']));
            }
        }
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_booking_id_unique');
            $table->dropColumn(['balance_due', 'original_amount', 'discount_rate', 'discount_amount']);
            $table->foreign('booking_id')->references('booking_id')->on('booking_bills')->cascadeOnDelete();
            $table->index(['booking_id', 'status']);
        });
        foreach (array_chunk($pendingPayments, 200) as $rows) {
            DB::table('payments')->insert($rows);
        }
        Schema::table('rooms', fn (Blueprint $table) => $table->dropColumn('capacity'));
    }

    public function down(): void
    {
        throw new RuntimeException('Multiple payment transactions cannot be merged back into one mutable payment row.');
    }
};
