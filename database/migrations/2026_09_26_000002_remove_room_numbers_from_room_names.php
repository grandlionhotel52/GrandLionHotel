<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $roomNames = [
        'Room 101 - Standard Queen' => 'Standard Queen',
        'Room 102 - Standard Twin' => 'Standard Twin',
        'Room 201 - Deluxe King' => 'Deluxe King',
        'Room 202 - Accessible King' => 'Accessible King',
        'Room 301 - Junior Suite' => 'Junior Suite',
        'Room 401 - Executive Suite' => 'Executive Suite',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('rooms') || !Schema::hasColumn('rooms', 'name')) {
            return;
        }

        foreach ($this->roomNames as $numberedName => $guestFacingName) {
            DB::table('rooms')
                ->where('name', $numberedName)
                ->update(['name' => $guestFacingName]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('rooms') || !Schema::hasColumn('rooms', 'name')) {
            return;
        }

        foreach ($this->roomNames as $numberedName => $guestFacingName) {
            DB::table('rooms')
                ->where('name', $guestFacingName)
                ->update(['name' => $numberedName]);
        }
    }
};
