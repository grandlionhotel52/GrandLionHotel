<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $statuses = [
            ['name' => 'Clean', 'slug' => 'clean', 'description' => 'Room is clean and ready for guests'],
            ['name' => 'Dirty', 'slug' => 'dirty', 'description' => 'Room is dirty and needs deep cleaning'],
            ['name' => 'Make Up Room', 'slug' => 'being_cleaned', 'description' => 'Room is currently under make-up room service'],
        ];

        foreach ($statuses as $status) {
            DB::table('room_status')->updateOrInsert(
                ['slug' => $status['slug']],
                $status + ['created_at' => $now, 'updated_at' => $now]
            );
        }

        $statusIds = DB::table('room_status')->pluck('room_status_id', 'slug');
        $images = [
            'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
        ];
        $rooms = [
            ['Nature Retreat', 'Standard', 'Nature View', 'Comfortable standard room with garden-side windows and work desk.', 2599.00, 'clean', $images[0]],
            ['Garden Haven', 'Standard', 'Garden View', 'Bright standard room with lush garden-facing windows and fast Wi-Fi.', 2699.00, 'clean', $images[1]],
            ['Poolside Deluxe Twin', 'Deluxe', 'Pool View', 'Deluxe twin room with premium bedding and minibar.', 3499.00, 'clean', $images[2]],
            ['Garden Deluxe King', 'Deluxe', 'Garden View', 'Spacious king room with lounge chair and rainfall shower.', 3799.00, 'being_cleaned', $images[3]],
            ['Courtyard Family Room', 'Family', 'Courtyard View', 'Family-friendly room with extra sleeping space and sofa bed.', 4599.00, 'clean', $images[4]],
            ['Poolside Family Room', 'Family', 'Pool View', 'Large family room near elevator with kid-safe fixtures.', 4899.00, 'dirty', $images[5]],
            ['Nature Junior Suite', 'Suite', 'Nature View', 'Suite with separate sitting area and premium toiletries.', 5699.00, 'clean', $images[2]],
            ['Mountain Executive Suite', 'Executive', 'Mountain View', 'Executive suite with workstation, meeting nook, and mountain-facing windows.', 6999.00, 'clean', $images[0]],
            ['Panorama Penthouse', 'Penthouse', 'Mountain View', 'Top-floor penthouse with private dining and panoramic windows.', 9999.00, 'dirty', $images[4]],
            ['Sunset Penthouse', 'Penthouse', 'Nature View', 'Luxury penthouse with sunset-facing balcony and lounge area.', 10499.00, 'clean', $images[1]],
        ];

        foreach ($rooms as [$name, $type, $view, $description, $price, $status, $image]) {
            DB::table('rooms')->updateOrInsert(
                ['name' => $name],
                [
                    'type' => $type,
                    'view_type' => $view,
                    'description' => $description,
                    'price_per_night' => $price,
                    'capacity' => 2,
                    'image' => $image,
                    'room_status_id' => $statusIds[$status],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        // Preserve hotel records if this migration is rolled back.
    }
};
