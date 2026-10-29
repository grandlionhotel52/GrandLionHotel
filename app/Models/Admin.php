<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Account
{
    protected $table = 'admins';

    protected $primaryKey = 'admin_id';

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'password_changed_at',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(static function (Admin $admin): void {
            $admin->is_active = true;
        });
    }

    public function getRoleAttribute(): string
    {
        return 'admin';
    }

    public function createdStaff(): HasMany
    {
        return $this->hasMany(Staff::class, 'created_by', 'admin_id');
    }

    public function statusUpdatedRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'admin_id', 'admin_id');
    }
}
