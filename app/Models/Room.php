<?php

namespace App\Models;

use App\Models\Concerns\HasEncryptedRouteKey;
use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Room extends Model
{
    use HasEncryptedRouteKey;
    use HasFactory;
    use HasLegacyIdAttribute;

    public const STANDARD_GUEST_CAPACITY = 2;

    public const BOOKABLE_ROOM_STATUS_SLUGS = ['clean'];

    public const FALLBACK_IMAGE_PATH = 'brand/hotel-placeholder.svg';

    private const DEFAULT_ROOM_IMAGES = [
        'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
    ];

    protected $primaryKey = 'room_id';

    protected $fillable = [
        'name',
        'type',
        'view_type',
        'description',
        'price_per_night',
        'capacity',
        'image',
        'room_status_id',
        'admin_id',
        'status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
            'status_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Room $room): void {
            $room->attributes['capacity'] = self::STANDARD_GUEST_CAPACITY;
        });
    }

    public static function standardGuestCapacity(): int
    {
        return self::STANDARD_GUEST_CAPACITY;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'room_id', 'room_id');
    }

    public function dateDiscounts(): HasMany
    {
        return $this->hasMany(RoomDateDiscount::class, 'room_id', 'room_id');
    }

    public function roomStatus(): BelongsTo
    {
        return $this->belongsTo(RoomStatus::class, 'room_status_id', 'room_status_id');
    }

    public function statusUpdatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'admin_id');
    }

    public function getImageUrlAttribute(): string
    {
        if (! empty($this->image)) {
            if (Str::startsWith($this->image, ['http://', 'https://'])) {
                return $this->image;
            }

            if (Storage::disk('public')->exists($this->image)) {
                return Storage::disk('public')->url($this->image);
            }

            return asset(self::FALLBACK_IMAGE_PATH);
        }

        $seed = $this->getKey() ?? crc32((string) $this->name);
        $index = abs((int) $seed) % count(self::DEFAULT_ROOM_IMAGES);

        return self::DEFAULT_ROOM_IMAGES[$index];
    }

    public function getPricePerHourAttribute(): float
    {
        return round((float) $this->price_per_night / 12, 2);
    }

    public function getAmenitiesAttribute(): array
    {
        $amenities = [
            ['label' => 'Free Wi-Fi', 'icon' => 'bi-wifi'],
            ['label' => 'Air conditioning', 'icon' => 'bi-snow'],
            ['label' => 'Private bathroom', 'icon' => 'bi-droplet'],
            ['label' => 'Smart TV', 'icon' => 'bi-tv'],
        ];

        $typeAmenities = match (strtolower(trim((string) $this->type))) {
            'family' => [
                ['label' => 'Family seating area', 'icon' => 'bi-people'],
                ['label' => 'Extra bed on request', 'icon' => 'bi-plus-square'],
            ],
            'suite', 'executive' => [
                ['label' => 'Separate sitting area', 'icon' => 'bi-lamp'],
                ['label' => 'Dedicated workspace', 'icon' => 'bi-briefcase'],
            ],
            'penthouse' => [
                ['label' => 'Private lounge', 'icon' => 'bi-stars'],
                ['label' => 'Premium room amenities', 'icon' => 'bi-gem'],
            ],
            'deluxe' => [
                ['label' => 'Premium bedding', 'icon' => 'bi-moon-stars'],
                ['label' => 'Rainfall shower', 'icon' => 'bi-cloud-rain'],
            ],
            default => [
                ['label' => 'Work desk', 'icon' => 'bi-laptop'],
                ['label' => 'Daily housekeeping', 'icon' => 'bi-check2-circle'],
            ],
        };

        return array_merge($amenities, $typeAmenities);
    }

    public function getIsAvailableAttribute(): bool
    {
        $roomStatusSlug = null;

        if ($this->relationLoaded('roomStatus')) {
            $roomStatusSlug = $this->roomStatus?->slug;
        } elseif (! is_null($this->room_status_id)) {
            $roomStatusSlug = $this->roomStatus()->value('slug');
        }

        return self::isBookableForCustomerByRoomStatus($roomStatusSlug);
    }

    public function setIsAvailableAttribute(mixed $value): void
    {
        $slug = filter_var($value, FILTER_VALIDATE_BOOL) ? 'clean' : 'dirty';
        $statusId = RoomStatus::query()->where('slug', $slug)->value('room_status_id');

        if (! is_null($statusId)) {
            $this->attributes['room_status_id'] = $statusId;
        }
    }

    public function scopeAvailableForBooking(Builder $query): Builder
    {
        return $query->whereHas('roomStatus', function (Builder $roomStatusQuery): void {
            $roomStatusQuery->whereIn('slug', self::BOOKABLE_ROOM_STATUS_SLUGS);
        });
    }

    public function scopeUnavailableForBooking(Builder $query): Builder
    {
        return $query->where(function (Builder $nested): void {
            $nested->whereNull('room_status_id')
                ->orWhereHas('roomStatus', function (Builder $roomStatusQuery): void {
                    $roomStatusQuery->whereNotIn('slug', self::BOOKABLE_ROOM_STATUS_SLUGS);
                });
        });
    }

    public function scopeOrderByAvailability(Builder $query, string $direction = 'desc'): Builder
    {
        $normalizedDirection = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $placeholders = implode(',', array_fill(0, count(self::BOOKABLE_ROOM_STATUS_SLUGS), '?'));

        return $query->orderByRaw(
            "CASE WHEN EXISTS (
                SELECT 1
                FROM room_status rs
                WHERE rs.room_status_id = rooms.room_status_id
                AND rs.slug IN ({$placeholders})
            ) THEN 1 ELSE 0 END {$normalizedDirection}",
            self::BOOKABLE_ROOM_STATUS_SLUGS
        );
    }

    public static function isBookableForCustomerByRoomStatus(?string $roomStatusSlug): bool
    {
        $slug = strtolower(trim((string) $roomStatusSlug));

        if ($slug === '') {
            return false;
        }

        return in_array($slug, self::BOOKABLE_ROOM_STATUS_SLUGS, true);
    }
}
