<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $primaryKey = 'system_setting_id';

    protected $fillable = [
        'setting_key',
        'value',
    ];

    public static function hotelName(): string
    {
        return (string) static::query()
            ->where('setting_key', 'hotel_name')
            ->value('value');
    }
}
