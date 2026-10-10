<?php

namespace App\Models;

use App\Models\Concerns\HasLegacyIdAttribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RoomImage extends Model
{
    use HasLegacyIdAttribute;

    protected $primaryKey = 'room_image_id';

    protected $fillable = [
        'path',
        'caption',
        'sort_order',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
