<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'hotelName' => config('app.name', 'The Grand Lion Hotel'),
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger)
    {
        $validated = $request->validate([
            'hotel_name' => ['required', 'string', 'max:100'],
        ]);

        $hotelName = trim($validated['hotel_name']);
        $setting = SystemSetting::query()->firstOrNew(['setting_key' => 'hotel_name']);
        $before = $setting->exists ? ['hotel_name' => $setting->value] : [];

        $setting->value = $hotelName;
        $setting->save();

        config(['app.name' => $hotelName]);
        $auditLogger->recordModel($setting, 'updated', $before, ['hotel_name' => $hotelName]);

        return back()->with('status', 'System title updated successfully.');
    }
}
