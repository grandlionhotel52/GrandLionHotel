<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONTACT_COLUMNS = [
        'first_name', 'last_name', 'email', 'phone', 'address_line',
        'street_address_line_2', 'city', 'province', 'postal_code',
    ];

    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('street_address_line_2')->nullable();
            $table->string('postal_code', 20)->nullable();
        });

        DB::transaction(function (): void {
            DB::table('booking_guest_details')->orderByDesc('guest_detail_id')
                ->chunkByIdDesc(200, function ($details): void {
                    foreach ($details as $detail) {
                        $booking = DB::table('bookings')->where('booking_id', $detail->booking_id)->first();
                        if (! $booking) {
                            throw new RuntimeException('Booking details refer to a missing booking.');
                        }

                        $contact = [
                            'name' => trim(trim((string) $detail->first_name).' '.trim((string) $detail->last_name)),
                        ];
                        foreach (array_diff(self::CONTACT_COLUMNS, ['first_name', 'last_name']) as $column) {
                            $contact[$column] = $detail->{$column};
                        }

                        $customer = $booking->customer_id
                            ? DB::table('customers')->where('customer_id', $booking->customer_id)->first()
                            : null;

                        // Reuse an identified customer without overwriting their profile.
                        if (! $customer && filled($contact['email'])) {
                            $customer = DB::table('customers')->whereRaw('LOWER(email) = ?', [strtolower(trim($contact['email']))])->first();
                        }

                        if (! $customer) {
                            $customerId = DB::table('customers')->insertGetId(array_merge($contact, [
                                'name' => $contact['name'] ?: 'Guest',
                                'email' => filled($contact['email']) ? strtolower(trim($contact['email'])) : null,
                                'password' => null,
                                'is_active' => true,
                                'created_at' => $detail->created_at ?? now(),
                                'updated_at' => now(),
                            ]), 'customer_id');
                            $customer = DB::table('customers')->where('customer_id', $customerId)->first();
                        }

                        $updates = [];
                        foreach ($contact as $column => $value) {
                            if (blank($customer->{$column}) && filled($value)) {
                                // Another customer may already own this email address.
                                if ($column === 'email' && DB::table('customers')->whereRaw('LOWER(email) = ?', [strtolower(trim($value))])
                                    ->where('customer_id', '!=', $customer->customer_id)->exists()) {
                                    continue;
                                }
                                $updates[$column] = $value;
                            }
                        }
                        if ($updates !== []) {
                            DB::table('customers')->where('customer_id', $customer->customer_id)->update($updates);
                            $customer = DB::table('customers')->where('customer_id', $customer->customer_id)->first();
                        }

                        DB::table('bookings')->where('booking_id', $booking->booking_id)->update(['customer_id' => $customer->customer_id]);

                        // Preserve differing legacy contact values as an immutable audit
                        // record, rather than retaining two editable customer profiles.
                        $differences = [];
                        foreach ($contact as $column => $value) {
                            if (filled($value) && trim((string) $value) !== trim((string) $customer->{$column})) {
                                $differences[$column] = $value;
                            }
                        }
                        if ($differences !== []) {
                            DB::table('activity_logs')->insert([
                                'actor_type' => null,
                                'actor_id' => null,
                                'action' => 'customer_details_normalized',
                                'subject_type' => 'Booking',
                                'subject_id' => $booking->booking_id,
                                'changes' => json_encode(['before' => $differences, 'after' => ['customer_id' => $customer->customer_id]], JSON_THROW_ON_ERROR),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }, 'guest_detail_id');
        });

        Schema::table('booking_guest_details', function (Blueprint $table): void {
            $table->dropColumn(self::CONTACT_COLUMNS);
        });
        Schema::rename('booking_guest_details', 'booking_details');
        Schema::table('booking_details', function (Blueprint $table): void {
            $table->renameColumn('guest_detail_id', 'booking_detail_id');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Customer normalization cannot be rolled back without restoring legacy contact snapshots.');
    }
};
