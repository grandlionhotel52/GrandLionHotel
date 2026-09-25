<?php

namespace Tests\Feature;

use Database\Seeders\RoomSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomInventoryNamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_facing_room_names_do_not_include_room_numbers(): void
    {
        $this->seed(RoomSeeder::class);

        $names = DB::table('rooms')->pluck('name');

        $this->assertTrue($names->contains('Deluxe King'));
        $this->assertTrue($names->contains('Standard Queen'));
        $this->assertFalse($names->contains(fn (string $name): bool => preg_match('/^Room\s+\d+/i', $name) === 1));
    }
}
