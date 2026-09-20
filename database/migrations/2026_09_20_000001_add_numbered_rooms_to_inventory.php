<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $cleanStatusId = DB::table('room_status')
            ->where('slug', 'clean')
            ->value('room_status_id');

        if (is_null($cleanStatusId)) {
            return;
        }

        $rooms = [
            ['Room 101 - Standard Queen', 'Standard', 'City View', 'Standard room with one queen bed, a work desk, and a city-facing window.', 2899.00, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80'],
            ['Room 102 - Standard Twin', 'Standard', 'Courtyard View', 'Standard room with two single beds, practical storage, and a quiet courtyard view.', 3299.00, 'https://images.unsplash.com/photo-1594563703937-fdc640497dcd?auto=format&fit=crop&w=1400&q=80'],
            ['Room 201 - Deluxe King', 'Deluxe', 'Pool View', 'Deluxe room with one king bed, premium bedding, a lounge chair, and a pool view.', 4299.00, 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1400&q=80'],
            ['Room 202 - Accessible King', 'Accessible', 'Courtyard View', 'Accessible room with one king bed, wide pathways, and step-free bathroom fixtures.', 3199.00, 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?auto=format&fit=crop&w=1400&q=80'],
            ['Room 301 - Junior Suite', 'Suite', 'City View', 'Junior suite with one king bed, a separate sitting area, and an expanded bathroom.', 6299.00, 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=1400&q=80'],
            ['Room 401 - Executive Suite', 'Executive', 'Mountain View', 'Executive suite with one king bed, a private living area, dining space, and mountain views.', 12999.00, 'https://images.unsplash.com/photo-1601918774946-25832a4be0d6?auto=format&fit=crop&w=1400&q=80'],
        ];

        foreach ($rooms as [$name, $type, $view, $description, $price, $image]) {
            $attributes = [
                'type' => $type,
                'view_type' => $view,
                'description' => $description,
                'price_per_night' => $price,
                'capacity' => 2,
                'image' => $image,
                'updated_at' => now(),
            ];

            $existingRoom = DB::table('rooms')->where('name', $name)->first();

            if ($existingRoom) {
                DB::table('rooms')
                    ->where('room_id', $existingRoom->room_id)
                    ->update($attributes);

                continue;
            }

            DB::table('rooms')->insert($attributes + [
                'name' => $name,
                'room_status_id' => $cleanStatusId,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Preserve room and booking history if this data migration is rolled back.
    }
};
