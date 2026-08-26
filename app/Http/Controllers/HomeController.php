<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomDateDiscount;

class HomeController extends Controller
{
    public function index()
    {
        $featuredRooms = Room::query()
            ->availableForBooking()
            ->latest()
            ->take(6)
            ->get();
        $roomCategories = Room::query()
            ->selectRaw('type, COUNT(*) as total')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->availableForBooking()
            ->groupBy('type')
            ->orderByDesc('total')
            ->orderBy('type')
            ->take(8)
            ->get();
        $roomSearchSuggestions = Room::query()
            ->get(['type', 'view_type'])
            ->flatMap(static fn (Room $room): array => [$room->type, $room->view_type])
            ->filter(static fn (mixed $value): bool => filled($value))
            ->map(static fn (mixed $value): string => trim((string) $value))
            ->unique(static fn (string $value): string => strtolower($value))
            ->sort()
            ->values();

        $platformStats = [
            'total_rooms' => Room::count(),
            'available_rooms' => Room::query()->availableForBooking()->count(),
            'starting_price' => Room::min('price_per_night'),
        ];

        $currentPromotion = RoomDateDiscount::query()
            ->with('room')
            ->whereDate('discount_date_end', '>=', now()->toDateString())
            ->orderByDesc('discount_percent')
            ->orderBy('discount_date_start')
            ->first();

        return view('home', compact('featuredRooms', 'roomCategories', 'roomSearchSuggestions', 'platformStats', 'currentPromotion'));
    }
}
