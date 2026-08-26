<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromoCodeController extends Controller
{
    public function index()
    {
        return view('admin.promo-codes.index', [
            'promoCodes' => PromoCode::query()->latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        PromoCode::create($this->validated($request));

        return back()->with('status', 'Promo code created successfully.');
    }

    public function update(Request $request, PromoCode $promoCode)
    {
        $promoCode->update($this->validated($request, $promoCode));

        return back()->with('status', 'Promo code updated successfully.');
    }

    public function destroy(PromoCode $promoCode)
    {
        if ($promoCode->bookingDiscounts()->exists()) {
            $promoCode->update(['is_active' => false]);

            return back()->with('status', 'Promo code was used by existing bookings, so it was deactivated instead of deleted.');
        }

        $promoCode->delete();

        return back()->with('status', 'Promo code deleted.');
    }

    private function validated(Request $request, ?PromoCode $promoCode = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('promo_codes', 'code')->ignore($promoCode)],
            'discount_percent' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
