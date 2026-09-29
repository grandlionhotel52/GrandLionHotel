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
    private const LEGACY_NAMES = [
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

    private const NAMES = [
        'Desiree T. Daroy',
        'Ryza Barbadillo',
        'Maurice Valdez',
        'Jane Cruz',
        'Mark Dave Deucalion',
        'Jazmine Ramos',
        'Mariella Santos',
        'Zhad Navarro',
        'Rayver Mendoza',
        'Justine Tamondong',
        'Zyril V. Deguzman',
        'John Marc Villanueva',
        'Josh Castillo',
        'Lovely Joy G. Baig',
        'Mariz Ballesteros',
        'Gil Allen C. Bautista',
        'Jaybee Estabillo',
        'Inoue De Leon',
        'Trisha Mae Gatpo',
        'Kyla V. Domingo',
        'Justine Garcia',
        'Rene Salazar',
        'Jericho Orala',
        'Sebastian Padilla',
        'Syrel L. Solomon',
        'Maruin S. Ferrer',
        'Lester Aquino',
        'Camila Hara',
        'Rane Gabrielle A. Palisoc',
        'Hans S. Mercado',
        'Anne Flores',
        'Ange Cabrera',
        'Jam Reyes',
        'Mark Soriano',
        'Jay Manalo',
        'Rogelyn De Vera',
        'Claire Pascual',
        'Renz Evangelista',
        'Mae Fernandez',
        'Clare Macaraeg',
        'Mike Dizon',
        'Jericho Alonzo',
        'Vincent Rivera',
        'Kien Hilomen',
        'Diether G. Estrada',
        'Aliah O. Balansay',
    ];

    private const PHONE_NUMBERS = [
        '09178342619', '09286519347', '09561284730', '09073418652', '09692570418',
        '09156293847', '09918437526', '09273485169', '09661429753', '09184736205',
        '09507841362', '09218753649', '09956312874', '09163847025', '09684721530',
        '09265938147', '09172640583', '09518472630', '09927531684', '09086342715',
        '09631857240', '09147582639', '09284617350', '09563728419', '09918426375',
        '09075263814', '09647318520', '09183627495', '09251734860', '09528641379',
        '09963417285', '09152874630', '09681532749', '09064728315', '09273841650',
        '09514782639', '09928536417', '09167423850', '09635281749', '09082647135',
        '09246371850', '09571826439', '09934617285', '09125783460', '09678423159',
        '09053862714',
    ];

    private const ADDITIONAL_CUSTOMERS = [
        ['name' => 'Andrea Villareal', 'phone' => '09170001001'],
        ['name' => 'Paolo Mendoza', 'phone' => '09170001002'],
        ['name' => 'Bianca Torres', 'phone' => '09170001003'],
        ['name' => 'Carlo Ramirez', 'phone' => '09170001004'],
        ['name' => 'Denise Santiago', 'phone' => '09170001005'],
        ['name' => 'Elijah Fernandez', 'phone' => '09170001006'],
        ['name' => 'Faith Gonzales', 'phone' => '09170001007'],
        ['name' => 'Gabriel Lim', 'phone' => '09170001008'],
        ['name' => 'Hannah Bautista', 'phone' => '09170001009'],
        ['name' => 'Ivan Castillo', 'phone' => '09170001010'],
        ['name' => 'Julia Navarro', 'phone' => '09170001011'],
        ['name' => 'Kevin Aquino', 'phone' => '09170001012'],
        ['name' => 'Lara Dominguez', 'phone' => '09170001013'],
        ['name' => 'Miguel Pascual', 'phone' => '09170001014'],
        ['name' => 'Nicole Salazar', 'phone' => '09170001015'],
        ['name' => 'Oscar Rivera', 'phone' => '09170001016'],
        ['name' => 'Patricia Reyes', 'phone' => '09170001017'],
        ['name' => 'Rafael Soriano', 'phone' => '09170001018'],
        ['name' => 'Samantha Valdez', 'phone' => '09170001019'],
        ['name' => 'Tristan Mercado', 'phone' => '09170001020'],
        ['name' => 'Uma Cabrera', 'phone' => '09170001021'],
        ['name' => 'Victor Manalo', 'phone' => '09170001022'],
        ['name' => 'Wendy Padilla', 'phone' => '09170001023'],
        ['name' => 'Xavier Flores', 'phone' => '09170001024'],
        ['name' => 'Yvonne Dizon', 'phone' => '09170001025'],
        ['name' => 'Zachary Alonzo', 'phone' => '09170001026'],
        ['name' => 'Alyssa Evangelista', 'phone' => '09170001027'],
        ['name' => 'Brandon Macaraeg', 'phone' => '09170001028'],
        ['name' => 'Clarissa Domingo', 'phone' => '09170001029'],
        ['name' => 'Dominic Villanueva', 'phone' => '09170001030'],
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
            $customers = array_merge(
                array_map(
                    static fn (string $name, int $index): array => [
                        'name' => $name,
                        'legacy_name' => self::LEGACY_NAMES[$index],
                        'phone' => self::PHONE_NUMBERS[$index],
                    ],
                    self::NAMES,
                    array_keys(self::NAMES)
                ),
                array_map(
                    static fn (array $customer): array => $customer + ['legacy_name' => $customer['name']],
                    self::ADDITIONAL_CUSTOMERS
                )
            );

            foreach ($customers as $index => $customer) {
                $name = $customer['name'];
                $number = $index + 1;
                $legacyName = $customer['legacy_name'];
                $phone = $customer['phone'];
                $joinedAt = Carbon::parse('2026-09-14 08:00:00')
                    ->addDays($index % 4)
                    ->addMinutes(($index * 17) % 720);
                $legacyEmail = $this->legacyEmail($legacyName);
                $email = $this->naturalEmail($name, $phone);
                [$city, $province] = self::LOCATIONS[$index % count(self::LOCATIONS)];
                $profileIsComplete = $index % 4 !== 0;

                $legacyCustomerId = DB::table('customers')->where('email', $legacyEmail)->value('customer_id');

                if ($legacyCustomerId) {
                    DB::table('customers')->where('customer_id', $legacyCustomerId)->update([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                    ]);
                } elseif (! DB::table('customers')->where('email', $email)->exists()) {
                    DB::table('customers')->insert([
                        'name' => $name,
                        'email' => $email,
                        'password' => $password,
                        'phone' => $phone,
                        'address_line' => $profileIsComplete ? $number.' '.self::LOCATIONS[($index + 1) % count(self::LOCATIONS)][0].' Road' : null,
                        'city' => $profileIsComplete ? $city : null,
                        'province' => $profileIsComplete ? $province : null,
                        'country' => 'Philippines',
                        'email_verified_at' => $joinedAt,
                        'is_active' => true,
                        'created_at' => $joinedAt,
                        'updated_at' => $joinedAt->copy()->addMinutes(5),
                    ]);
                }

                $customerId = (int) DB::table('customers')->where('email', $email)->value('customer_id');

                DB::table('customers')->where('customer_id', $customerId)->update([
                    'email_verified_at' => $joinedAt,
                    'created_at' => $joinedAt,
                    'updated_at' => $joinedAt->copy()->addMinutes(5),
                ]);

                $this->seedCustomerActivity($customerId, $name, $email, $joinedAt, $index, $adminId);

                $bookingCount = $index >= count(self::NAMES)
                    ? 0
                    : ($index % 10 === 0 ? 2 : ($index % 3 === 0 ? 0 : 1));
                for ($bookingIndex = 0; $bookingIndex < $bookingCount; $bookingIndex++) {
                    $this->seedBooking(
                        $customerId,
                        $name,
                        $email,
                        $legacyEmail,
                        $phone,
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
        string $legacyEmail,
        string $phone,
        Carbon $joinedAt,
        int $index,
        int $bookingIndex,
        $rooms
    ): void {
        $room = $rooms[($index + $bookingIndex) % $rooms->count()];
        $createdAt = $joinedAt->copy()->addHours(2 + $bookingIndex);
        $checkIn = Carbon::parse('2026-09-19')->addDays($index + ($bookingIndex * 2));
        $checkOut = $checkIn->copy()->addDays(2);
        $status = $checkOut->isPast() ? 'completed' : 'confirmed';
        $legacyMarker = "Requested demo history: {$legacyEmail} #".($bookingIndex + 1);
        $notes = [
            'Late arrival requested.',
            'Quiet room preferred.',
            'Please call before check-in.',
            'Near the elevator if available.',
            null,
        ][$index % 5];
        $existingBookingId = DB::table('bookings')
            ->where('customer_id', $customerId)
            ->where(function ($query) use ($createdAt, $legacyMarker): void {
                $query->where('created_at', $createdAt)
                    ->orWhere('notes', $legacyMarker);
            })
            ->value('booking_id');

        $parts = preg_split('/\s+/', trim($name), 2);

        if ($existingBookingId) {
            DB::table('bookings')->where('booking_id', $existingBookingId)->update(['notes' => $notes]);
            DB::table('booking_guest_details')->where('booking_id', $existingBookingId)->update([
                'first_name' => $parts[0],
                'last_name' => $parts[1],
                'email' => $email,
                'phone' => $phone,
            ]);
            DB::table('payments')->where('booking_id', $existingBookingId)->update([
                'transaction_reference' => $this->transactionReference($createdAt, (int) $existingBookingId),
            ]);

            return;
        }

        $bookingId = DB::table('bookings')->insertGetId([
            'customer_id' => $customerId,
            'room_id' => $room->room_id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'status' => $status,
            'notes' => $notes,
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addHour(),
        ], 'booking_id');

        DB::table('booking_guest_details')->insert([
            'booking_id' => $bookingId,
            'first_name' => $parts[0],
            'last_name' => $parts[1],
            'email' => $email,
            'phone' => $phone,
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
            'transaction_reference' => $this->transactionReference($createdAt, (int) $bookingId),
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

    private function legacyEmail(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->append('.demo@gmail.com')
            ->toString();
    }

    private function naturalEmail(string $name, string $phone): string
    {
        $localPart = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->append(substr($phone, -2));

        return $localPart.'@gmail.com';
    }

    private function transactionReference(Carbon $createdAt, int $bookingId): string
    {
        return 'GLH-'.$createdAt->format('ymd').'-'.str_pad((string) $bookingId, 6, '0', STR_PAD_LEFT);
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
