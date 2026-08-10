<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomStatus;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $roomStatusIds = RoomStatus::query()->pluck('room_status_id', 'slug');

        $sampleRooms = [
            [
                'name' => 'Nature Retreat',
                'type' => 'Standard',
                'view_type' => 'Nature View',
                'description' => 'Comfortable standard room with garden-side windows and work desk.',
                'price_per_night' => 2599.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Garden Haven',
                'type' => 'Standard',
                'view_type' => 'Garden View',
                'description' => 'Bright standard room with lush garden-facing windows and fast Wi-Fi.',
                'price_per_night' => 2699.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Poolside Deluxe Twin',
                'type' => 'Deluxe',
                'view_type' => 'Pool View',
                'description' => 'Deluxe twin room with premium bedding and minibar.',
                'price_per_night' => 3499.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Garden Deluxe King',
                'type' => 'Deluxe',
                'view_type' => 'Garden View',
                'description' => 'Spacious king room with lounge chair and rainfall shower.',
                'price_per_night' => 3799.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'being_cleaned',
            ],
            [
                'name' => 'Courtyard Family Room',
                'type' => 'Family',
                'view_type' => 'Courtyard View',
                'description' => 'Family-friendly room with extra sleeping space and sofa bed.',
                'price_per_night' => 4599.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Poolside Family Room',
                'type' => 'Family',
                'view_type' => 'Pool View',
                'description' => 'Large family room near elevator with kid-safe fixtures.',
                'price_per_night' => 4899.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'dirty',
            ],
            [
                'name' => 'Nature Junior Suite',
                'type' => 'Suite',
                'view_type' => 'Nature View',
                'description' => 'Suite with separate sitting area and premium toiletries.',
                'price_per_night' => 5699.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Mountain Executive Suite',
                'type' => 'Executive',
                'view_type' => 'Mountain View',
                'description' => 'Executive suite with workstation, meeting nook, and mountain-facing windows.',
                'price_per_night' => 6999.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'clean',
            ],
            [
                'name' => 'Panorama Penthouse',
                'type' => 'Penthouse',
                'view_type' => 'Mountain View',
                'description' => 'Top-floor penthouse with private dining and panoramic windows.',
                'price_per_night' => 9999.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'dirty',
            ],
            [
                'name' => 'Sunset Penthouse',
                'type' => 'Penthouse',
                'view_type' => 'Nature View',
                'description' => 'Luxury penthouse with sunset-facing balcony and lounge area.',
                'price_per_night' => 10499.00,
                'capacity' => 2,
                'image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
                'room_status_slug' => 'clean',
            ],
        ];

        foreach ($sampleRooms as $room) {
            $roomStatusSlug = $room['room_status_slug'];

            unset($room['room_status_slug']);

            $room['room_status_id'] = $roomStatusIds[$roomStatusSlug] ?? null;

            Room::query()->updateOrCreate(
                ['name' => $room['name']],
                $room
            );
        }
    }
}
