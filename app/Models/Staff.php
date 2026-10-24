<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Account
{
    protected $table = 'users';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'password_changed_at',
        'remember_token',
        'created_by',
        'admin_id',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('staff_role', static fn (Builder $query) => $query->where('role', 'staff'));
        static::creating(static function (Staff $staff): void {
            $staff->role = 'staff';
        });
    }

    public function getAdminIdAttribute(): mixed
    {
        return $this->getAttribute('created_by');
    }

    public function setAdminIdAttribute(mixed $value): void
    {
        $this->attributes['created_by'] = $value;
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by', 'user_id');
    }

    public function assignedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'staff_id', 'user_id');
    }
}
