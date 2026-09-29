<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CUSTOMER_EMAILS_FOR_SEPTEMBER_15 = [
        'justinnamoro8@gmail.com',
        'ludwigjvdevera@gmail.com',
        'castromarkwill76@gmail.com',
        'nakiriayame75@gmail.com',
        'aquinoroland439@gmail.com',
        'ariannekatefernandez@gmail.com',
    ];

    private const REMOVED_CUSTOMER_EMAIL = 'sheilamaezing.24@gmail.com';

    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        DB::transaction(function (): void {
            $this->removeRequestedCustomer();
            $this->moveRequestedCustomersToSeptember15();
        });
    }

    public function down(): void
    {
        // The customer removal and requested presentation dates are intentionally retained.
    }

    private function removeRequestedCustomer(): void
    {
        $customerId = DB::table('customers')
            ->whereRaw('LOWER(email) = ?', [self::REMOVED_CUSTOMER_EMAIL])
            ->value('customer_id');

        if (! $customerId) {
            return;
        }

        $bookingIds = Schema::hasTable('bookings')
            ? DB::table('bookings')->where('customer_id', $customerId)->pluck('booking_id')
            : collect();
        $paymentIds = Schema::hasTable('payments') && $bookingIds->isNotEmpty()
            ? DB::table('payments')->whereIn('booking_id', $bookingIds)->pluck('payment_id')
            : collect();

        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')
                ->where(function ($query) use ($customerId): void {
                    $query->where(function ($customerQuery) use ($customerId): void {
                        $customerQuery->where('subject_type', 'Customer')
                            ->where('subject_id', $customerId);
                    })->orWhere(function ($actorQuery) use ($customerId): void {
                        $actorQuery->where('actor_type', 'Customer')
                            ->where('actor_id', $customerId);
                    });
                })
                ->delete();

            if ($bookingIds->isNotEmpty()) {
                DB::table('activity_logs')
                    ->where('subject_type', 'Booking')
                    ->whereIn('subject_id', $bookingIds)
                    ->delete();
            }

            if ($paymentIds->isNotEmpty()) {
                DB::table('activity_logs')
                    ->where('subject_type', 'Payment')
                    ->whereIn('subject_id', $paymentIds)
                    ->delete();
            }
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\Customer')
                ->where('notifiable_id', $customerId)
                ->delete();

            if ($bookingIds->isNotEmpty()) {
                $bookingIdLookup = array_fill_keys(
                    array_map('strval', $bookingIds->all()),
                    true
                );
                $notificationIds = DB::table('notifications')
                    ->get(['id', 'data'])
                    ->filter(function (object $notification) use ($bookingIdLookup): bool {
                        $data = json_decode((string) $notification->data, true);

                        return is_array($data)
                            && isset($bookingIdLookup[(string) ($data['booking_id'] ?? '')]);
                    })
                    ->pluck('id');

                if ($notificationIds->isNotEmpty()) {
                    DB::table('notifications')->whereIn('id', $notificationIds)->delete();
                }
            }
        }

        if ($bookingIds->isNotEmpty()) {
            DB::table('bookings')->whereIn('booking_id', $bookingIds)->delete();
        }

        DB::table('customers')->where('customer_id', $customerId)->delete();
    }

    private function moveRequestedCustomersToSeptember15(): void
    {
        $customers = DB::table('customers')
            ->whereIn(DB::raw('LOWER(email)'), self::CUSTOMER_EMAILS_FOR_SEPTEMBER_15)
            ->get(['customer_id', 'created_at', 'email_verified_at']);

        foreach ($customers as $customer) {
            $originalCreatedAt = Carbon::parse($customer->created_at);
            $createdAt = $originalCreatedAt->copy()->setDate(2026, 9, 15);
            $updates = ['created_at' => $createdAt];

            if ($customer->email_verified_at !== null) {
                $updates['email_verified_at'] = Carbon::parse($customer->email_verified_at)
                    ->setDate(2026, 9, 15);
            }

            DB::table('customers')
                ->where('customer_id', $customer->customer_id)
                ->update($updates);

            if (Schema::hasTable('activity_logs')) {
                DB::table('activity_logs')
                    ->where('subject_type', 'Customer')
                    ->where('subject_id', $customer->customer_id)
                    ->whereIn('action', ['created', 'logged_in'])
                    ->update([
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
            }
        }
    }
};
