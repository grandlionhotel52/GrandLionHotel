<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ROOM_NAMES = [
        'Room 101' => 'Nature Retreat',
        'Room 102' => 'Garden Haven',
        'Room 201 - Deluxe Twin' => 'Poolside Deluxe Twin',
        'Room 202 - Deluxe King' => 'Garden Deluxe King',
        'Room 301 - Family Comfort' => 'Courtyard Family Room',
        'Room 302 - Family Plus' => 'Poolside Family Room',
        'Room 401 - Junior Suite' => 'Nature Junior Suite',
        'Room 402 - Executive Suite' => 'Mountain Executive Suite',
        'Room 501 - Penthouse East' => 'Panorama Penthouse',
        'Room 502 - Penthouse West' => 'Sunset Penthouse',
    ];

    public function up(): void
    {
        foreach (self::ROOM_NAMES as $oldName => $newName) {
            DB::table('rooms')->where('name', $oldName)->update(['name' => $newName]);
        }
    }

    public function down(): void
    {
        foreach (self::ROOM_NAMES as $oldName => $newName) {
            DB::table('rooms')->where('name', $newName)->update(['name' => $oldName]);
        }
    }
};
