<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Account
{
    protected $table = 'staff';

    protected $primaryKey = 'staff_id';

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

    public function getRoleAttribute(): string
    {
        return 'staff';
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
        return $this->belongsTo(Admin::class, 'created_by', 'admin_id');
    }

    public function assignedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'staff_id', 'staff_id');
    }
}
