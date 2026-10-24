<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admin extends Account
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
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('admin_role', static fn (Builder $query) => $query->where('role', 'admin'));
        static::creating(static function (Admin $admin): void {
            $admin->role = 'admin';
            $admin->is_active = true;
        });
    }

    public function createdStaff(): HasMany
    {
        return $this->hasMany(Staff::class, 'created_by', 'user_id');
    }

    public function statusUpdatedRooms(): HasMany
    {
        return $this->hasMany(Room::class, 'admin_id', 'user_id');
    }
}
