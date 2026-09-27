<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class RequestedCustomerHistorySeeder extends Seeder
{
    private const NAMES = [
        'Desiree T. Daroy',
        'Ryza Barbadillo',
        'Maurice Gew',
        'Jane Cruz',
        'Mark Dave Deucalion',
        'Jazmine Gew',
        'Mariella Gew',
        'Zhad Gew',
        'Rayver Gew',
        'Justine Tamondong',
        'Zyril V. Deguzman',
        'John Marc Gew',
        'Josh Gew',
        'Lovely Joy G. Baig',
        'Mariz Ballesteros',
        'Gil Allen C. Gew',
        'Jaybee Estabillo',
        'Inoue De Leon',
        'Trisha Mae Gatpo',
        'Kyla V. Gew',
        'Justine Garcia',
        'Rene Gew',
        'Jericho Orala',
        'Sebastian Gew',
        'Syrel L. Solomon',
        'Maruin S. Ferrer',
        'Lester Gew',
        'Camila Hara',
        'Rane Gabrielle A. Palisoc',
        'Hans S. Gew',
        'Anne Gew',
        'Ange Gew',
        'Jam Gew',
        'Mark Gew',
        'Jay Gew',
        'Rogelyn De Vera',
        'Claire Gew',
        'Renz Gew',
        'Mae Gew',
        'Clare Gew',
        'Mike Gew',
        'Jericho Gew',
        'Vincent Gew',
        'Kien Hilomen',
        'Diether G. Estrada',
        'Aliah O. Balansay',
    ];

    private const LOCATIONS = [
        ['Calasiao', 'Pangasinan'],
        ['San Carlos City', 'Pangasinan'],
        ['Dagupan City', 'Pangasinan'],
        ['Urdaneta City', 'Pangasinan'],
    ];

    private const SOURCES = [
        ['10.31.64.171', 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/126.0 Mobile Safari/537.36'],
        ['10.29.162.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36 Edg/126.0'],
        ['10.31.18.12', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36 Edg/126.0'],
        ['192.168.1.24', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Version/17.5 Mobile Safari/604.1'],
    ];

    public function run(): void
    {
        $rooms = DB::table('rooms')
            ->orderBy('room_id')
            ->get(['room_id', 'price_per_night']);

        if ($rooms->isEmpty()) {
            throw new RuntimeException('Seed the room inventory before adding requested customer booking history.');
        }

        $adminId = DB::table('admins')->orderBy('admin_id')->value('admin_id');
        $password = Hash::make('Customer@123');

        DB::transaction(function () use ($rooms, $adminId, $password): void {
            foreach (self::NAMES as $index => $name) {
                $number = $index + 1;
                $joinedAt = Carbon::parse('2026-09-14 08:00:00')
                    ->addDays($index % 5)
                    ->addMinutes($index * 17);
                $email = Str::of($name)
                    ->ascii()
                    ->lower()
                    ->replaceMatches('/[^a-z0-9]+/', '.')
                    ->trim('.')
                    ->append('.demo@gmail.com')
                    ->toString();
                [$city, $province] = self::LOCATIONS[$index % count(self::LOCATIONS)];
                $profileIsComplete = $index % 4 !== 0;

                DB::table('customers')->insertOrIgnore([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'phone' => '0917'.str_pad((string) (1000000 + $number), 7, '0', STR_PAD_LEFT),
                    'address_line' => $profileIsComplete ? $number.' Demo Street' : null,
                    'city' => $profileIsComplete ? $city : null,
                    'province' => $profileIsComplete ? $province : null,
                    'country' => 'Philippines',
                    'email_verified_at' => $joinedAt,
                    'is_active' => true,
                    'created_at' => $joinedAt,
                    'updated_at' => $joinedAt->copy()->addMinutes(5),
                ]);

                $customerId = (int) DB::table('customers')->where('email', $email)->value('customer_id');

                $this->seedCustomerActivity($customerId, $name, $email, $joinedAt, $index, $adminId);

                $bookingCount = $index % 10 === 0 ? 2 : ($index % 3 === 0 ? 0 : 1);
                for ($bookingIndex = 0; $bookingIndex < $bookingCount; $bookingIndex++) {
                    $this->seedBooking(
                        $customerId,
                        $name,
                        $email,
                        $number,
                        $joinedAt,
                        $index,
                        $bookingIndex,
                        $rooms
                    );
                }
            }
        });
    }

    private function seedCustomerActivity(
        int $customerId,
        string $name,
        string $email,
        Carbon $joinedAt,
        int $index,
        mixed $adminId
    ): void {
        [$ipAddress, $userAgent] = self::SOURCES[$index % count(self::SOURCES)];

        $this->updateOrInsertActivity(
            'created',
            'Customer',
            $customerId,
            $adminId ? 'Admin' : null,
            $adminId ? (int) $adminId : null,
            ['before' => [], 'after' => ['name' => $name, 'email' => $email]],
            $ipAddress,
            $userAgent,
            $joinedAt
        );

        $this->updateOrInsertActivity(
            'logged_in',
            'Customer',
            $customerId,
            'Customer',
            $customerId,
            ['before' => [], 'after' => ['guard' => 'customer']],
            $ipAddress,
            $userAgent,
            $joinedAt->copy()->addMinutes(12)
        );
    }

    private function seedBooking(
        int $customerId,
        string $name,
        string $email,
        int $number,
        Carbon $joinedAt,
        int $index,
        int $bookingIndex,
        $rooms
    ): void {
        $marker = "Requested demo history: {$email} #".($bookingIndex + 1);
        $existingBookingId = DB::table('bookings')->where('notes', $marker)->value('booking_id');

        if ($existingBookingId) {
            return;
        }

        $room = $rooms[($index + $bookingIndex) % $rooms->count()];
        $createdAt = $joinedAt->copy()->addHours(2 + $bookingIndex);
        $checkIn = Carbon::parse('2026-09-19')->addDays($index + ($bookingIndex * 2));
        $checkOut = $checkIn->copy()->addDays(2);
        $status = $checkOut->isPast() ? 'completed' : 'confirmed';

        $bookingId = DB::table('bookings')->insertGetId([
            'customer_id' => $customerId,
            'room_id' => $room->room_id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => $status,
            'notes' => $marker,
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addHour(),
        ], 'booking_id');

        $parts = preg_split('/\s+/', trim($name), 2);
        DB::table('booking_guest_details')->insert([
            'booking_id' => $bookingId,
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? 'Gew',
            'email' => $email,
            'phone' => '0917'.str_pad((string) (1000000 + $number), 7, '0', STR_PAD_LEFT),
            'adults' => 2,
            'kids' => 0,
            'meal_plan' => 'room_only',
            'payment_preference' => 'cash',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $amount = round((float) $room->price_per_night * 2, 2);
        DB::table('payments')->insert([
            'booking_id' => $bookingId,
            'amount' => $amount,
            'balance_due' => 0,
            'method' => 'cash',
            'status' => 'paid',
            'transaction_reference' => 'DEMO-'.str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT),
            'paid_at' => $createdAt->copy()->addMinutes(30),
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addMinutes(30),
        ]);

        [$ipAddress, $userAgent] = self::SOURCES[($index + $bookingIndex) % count(self::SOURCES)];
        $this->updateOrInsertActivity(
            'created',
            'Booking',
            (int) $bookingId,
            'Customer',
            $customerId,
            ['before' => [], 'after' => ['customer_id' => $customerId, 'status' => $status]],
            $ipAddress,
            $userAgent,
            $createdAt
        );
    }

    private function updateOrInsertActivity(
        string $action,
        string $subjectType,
        int $subjectId,
        ?string $actorType,
        ?int $actorId,
        array $changes,
        string $ipAddress,
        string $userAgent,
        Carbon $occurredAt
    ): void {
        DB::table('activity_logs')->updateOrInsert(
            [
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
            ],
            [
                'changes' => json_encode($changes),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]
        );
    }
}
