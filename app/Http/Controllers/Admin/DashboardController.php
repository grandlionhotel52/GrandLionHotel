<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Staff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();
        $yearStart = Carbon::today()->startOfYear();
        $yearEnd = Carbon::today()->endOfYear();

        $stats = [
            'users' => Admin::count() + Staff::count() + Customer::count(),
            'staff' => Staff::count(),
            'customers' => Customer::count(),
            'rooms' => Room::count(),
            'available_rooms' => Room::query()->availableForBooking()->count(),
            'bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'daily_paid_revenue' => Payment::query()
                ->where('status', 'paid')
                ->whereDate('paid_at', $today)
                ->sum('amount'),
            'yearly_paid_revenue' => Payment::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$yearStart, $yearEnd])
                ->sum('amount'),
            'unpaid_confirmed' => Booking::where('status', 'confirmed')
                ->wherePaymentStatus('unpaid')
                ->count(),
            'arrivals_today' => Booking::whereDate('check_in', $today)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNull('actual_check_in_at')
                ->count(),
            'departures_today' => Booking::whereDate('check_out', $today)
                ->where('status', 'confirmed')
                ->whereNotNull('actual_check_in_at')
                ->whereNull('actual_check_out_at')
                ->count(),
            'monthly_paid_revenue' => Payment::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount'),
            'rooms_needing_attention' => Room::where(function ($query): void {
                $query->unavailableForBooking()
                    ->orWhereHas('roomStatus', fn ($roomStatusQuery) => $roomStatusQuery->where('slug', 'dirty'));
            })->count(),
        ];

        $recentBookings = Booking::query()
            ->with(['user', 'room', 'payment', 'guestDetail', 'assignedStaff'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentBookings'));
    }

    public function salesReport(Request $request)
    {
        $from = $this->normalizeDateInput($request->string('from')->toString()) ?? now()->startOfMonth()->toDateString();
        $to = $this->normalizeDateInput($request->string('to')->toString()) ?? now()->endOfMonth()->toDateString();
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }

        $rangeStart = Carbon::parse($from)->startOfDay();
        $rangeEnd = Carbon::parse($to)->startOfDay();
        if ($rangeStart->diffInDays($rangeEnd) > 366) {
            $to = $rangeStart->copy()->addDays(366)->toDateString();
        }

        $method = trim(strtolower($request->string('method')->toString()));
        if (! in_array($method, array_merge(['all'], Payment::allowedMethods()), true)) {
            $method = 'all';
        }

        $paymentsQuery = Payment::query()
            ->join('bookings', 'bookings.booking_id', '=', 'payments.booking_id')
            ->leftJoin('booking_guest_details', 'booking_guest_details.booking_id', '=', 'bookings.booking_id')
            ->leftJoin('booking_discounts', 'booking_discounts.booking_id', '=', 'bookings.booking_id')
            ->leftJoin('staff', 'staff.staff_id', '=', 'bookings.staff_id')
            ->where('payments.status', 'paid')
            ->whereNotNull('payments.paid_at')
            ->whereDate('payments.paid_at', '>=', $from)
            ->whereDate('payments.paid_at', '<=', $to);

        if ($method !== 'all') {
            $paymentsQuery->where('payments.method', $method);
        }

        $payments = (clone $paymentsQuery)
            ->select([
                'payments.payment_id',
                'payments.booking_id',
                'payments.amount',
                'payments.method',
                'payments.status',
                'payments.discount_amount',
                'booking_guest_details.meal_plan',
                'booking_discounts.discount_type',
                'payments.paid_at',
                'bookings.staff_id as assigned_staff_id',
                'staff.name as assigned_staff_name',
            ])
            ->orderByDesc('payments.paid_at')
            ->get()
            ->each(function ($payment): void {
                foreach ($this->paymentTaxComponents($payment) as $key => $value) {
                    $payment->setAttribute($key, $value);
                }

                $amount = round(max(0, (float) $payment->amount), 2);
                $foodSales = $payment->meal_plan === 'breakfast_included'
                    ? min($amount, round(max(0, (float) config('pricing.breakfast_fee', 1200)), 2))
                    : 0.0;

                $payment->setAttribute('report_food_sales', $foodSales);
                $payment->setAttribute('report_room_sales', round($amount - $foodSales, 2));
            });

        $grossRevenue = (float) $payments->sum(static fn ($payment): float => (float) $payment->amount);
        $paidBookings = $payments->count();

        $summary = [
            'total_revenue' => $grossRevenue,
            'gross_revenue' => $grossRevenue,
            'room_sales' => (float) $payments->sum('report_room_sales'),
            'food_sales' => (float) $payments->sum('report_food_sales'),
            'gross_sales_before_discount' => (float) $payments->sum('report_gross_sales'),
            'paid_bookings' => $paidBookings,
            'average_sale' => $paidBookings > 0 ? round($grossRevenue / $paidBookings, 2) : 0.0,
            'total_discount' => (float) $payments->sum(static fn ($payment): float => (float) ($payment->discount_amount ?? 0)),
            'net_sales_excluding_vat' => (float) $payments->sum('report_net_sales'),
            'vat_exempt_sales' => (float) $payments->sum('report_vat_exempt_sales'),
            'vat_total' => (float) $payments->sum('report_vat'),
            'local_tax_total' => (float) $payments->sum('report_local_tax'),
            'online_payments' => $payments->filter(static fn ($payment): bool => Payment::isOnlineMethod((string) $payment->method))->count(),
            'cash_payments' => $payments->where('method', Payment::METHOD_CASH)->count(),
        ];

        $paymentsByDate = $payments->groupBy(
            static fn ($payment): string => Carbon::parse($payment->paid_at)->toDateString()
        );
        $dailySales = $paymentsByDate->keys()
            ->map(static function (string $date) use ($paymentsByDate): object {
                $paymentRows = $paymentsByDate->get($date, collect());
                $gross = (float) $paymentRows->sum(static fn ($payment): float => (float) $payment->amount);

                return (object) [
                    'date' => $date,
                    'paid_bookings' => $paymentRows->count(),
                    'gross_revenue' => $gross,
                    'revenue' => $gross,
                    'discount_total' => (float) $paymentRows->sum(static fn ($payment): float => (float) ($payment->discount_amount ?? 0)),
                    'net_sales_excluding_vat' => (float) $paymentRows->sum('report_net_sales'),
                    'vat_total' => (float) $paymentRows->sum('report_vat'),
                    'local_tax_total' => (float) $paymentRows->sum('report_local_tax'),
                ];
            })
            ->sortByDesc(static fn (object $row): string => $row->date)
            ->values();

        $paymentsByMethod = $payments->groupBy(static fn ($payment): string => (string) $payment->method);
        $methodBreakdown = $paymentsByMethod->keys()
            ->map(static function (string $methodName) use ($paymentsByMethod): object {
                $paymentRows = $paymentsByMethod->get($methodName, collect());
                $gross = (float) $paymentRows->sum(static fn ($payment): float => (float) $payment->amount);

                return (object) [
                    'method' => $methodName,
                    'paid_bookings' => $paymentRows->count(),
                    'gross_revenue' => $gross,
                    'revenue' => $gross,
                ];
            })
            ->sortByDesc(static fn (object $row): float => $row->revenue)
            ->values();

        $staffKey = static function ($row): string {
            $staffId = (int) ($row->assigned_staff_id ?? 0);
            $staffName = trim((string) ($row->assigned_staff_name ?? ''));

            return $staffId > 0 ? 'staff:'.$staffId : 'staff:unassigned:'.($staffName !== '' ? $staffName : 'Unassigned');
        };
        $paymentsByStaff = $payments->groupBy($staffKey);
        $staffBreakdown = $paymentsByStaff->keys()
            ->map(static function (string $key) use ($paymentsByStaff): object {
                $paymentRows = $paymentsByStaff->get($key, collect());
                $first = $paymentRows->first();
                $staffId = (int) ($first->assigned_staff_id ?? 0);
                $staffName = trim((string) ($first->assigned_staff_name ?? ''));
                $gross = (float) $paymentRows->sum(static fn ($payment): float => (float) $payment->amount);

                return (object) [
                    'staff_id' => $staffId > 0 ? $staffId : null,
                    'staff_name' => $staffName !== '' ? $staffName : 'Unassigned',
                    'paid_bookings' => $paymentRows->count(),
                    'gross_revenue' => $gross,
                    'revenue' => $gross,
                ];
            })
            ->sortByDesc(static fn (object $row): float => $row->revenue)
            ->values();

        $recentSales = $payments->take(20)->values();
        $selectedRangeLabel = Carbon::parse($from)->format('M d, Y').' - '.Carbon::parse($to)->format('M d, Y');

        return view('admin.sales-report', compact(
            'summary',
            'dailySales',
            'methodBreakdown',
            'staffBreakdown',
            'recentSales',
            'from',
            'to',
            'method',
            'selectedRangeLabel'
        ));
    }

    public function occupancyReport(Request $request)
    {
        $from = $this->normalizeDateInput($request->string('from')->toString()) ?? now()->startOfMonth()->toDateString();
        $to = $this->normalizeDateInput($request->string('to')->toString()) ?? now()->endOfMonth()->toDateString();
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }

        $rangeStart = Carbon::parse($from);
        $rangeEnd = Carbon::parse($to);
        if ($rangeStart->diffInDays($rangeEnd) > 366) {
            $rangeEnd = $rangeStart->copy()->addDays(366);
            $to = $rangeEnd->toDateString();
        }

        $rooms = Room::query()->count();
        $bookings = Booking::query()
            ->with('room:room_id,name,type')
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereDate('check_in', '<=', $to)
            ->whereDate('check_out', '>', $from)
            ->get();

        $dailyOccupancy = collect(CarbonPeriod::create($rangeStart, $rangeEnd))
            ->map(function (Carbon $date) use ($bookings, $rooms): object {
                $occupiedRooms = $bookings
                    ->filter(fn (Booking $booking): bool => $booking->check_in->lte($date) && $booking->check_out->gt($date))
                    ->pluck('room_id')
                    ->unique()
                    ->count();

                return (object) [
                    'date' => $date->toDateString(),
                    'occupied_rooms' => $occupiedRooms,
                    'available_rooms' => max(0, $rooms - $occupiedRooms),
                    'occupancy_rate' => $rooms > 0 ? round(($occupiedRooms / $rooms) * 100, 1) : 0,
                ];
            });

        $roomNightsAvailable = $rooms * $dailyOccupancy->count();
        $roomNightsSold = (int) $dailyOccupancy->sum('occupied_rooms');
        $summary = [
            'rooms' => $rooms,
            'room_nights_available' => $roomNightsAvailable,
            'room_nights_sold' => $roomNightsSold,
            'occupancy_rate' => $roomNightsAvailable > 0 ? round(($roomNightsSold / $roomNightsAvailable) * 100, 1) : 0,
            'peak_day' => $dailyOccupancy->sortByDesc('occupied_rooms')->first(),
        ];

        return view('admin.occupancy-report', compact('summary', 'dailyOccupancy', 'from', 'to'));
    }

    private function normalizeDateInput(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Split the stored amount actually charged into its accounting components.
     * Stored payments remain the source of truth so historic reports do not
     * change when room prices or discounts are edited later.
     */
    private function paymentTaxComponents(object $payment): array
    {
        $amount = round(max(0, (float) ($payment->amount ?? 0)), 2);
        $discount = round(max(0, (float) ($payment->discount_amount ?? 0)), 2);
        $discountType = strtolower(trim((string) ($payment->discount_type ?? '')));
        $isVatExempt = in_array($discountType, ['pwd', 'senior'], true) && $discount > 0;
        $localTaxRate = max(0, (float) config('pricing.local_tax_rate', 0.05));
        $vatRate = max(0, (float) config('pricing.vat_rate', 0.12));

        if ($isVatExempt) {
            $localTaxApplies = filter_var(
                config('pricing.local_tax_applies_to_vat_exempt_sales', true),
                FILTER_VALIDATE_BOOL
            );
            $netSales = $localTaxApplies
                ? round($amount / (1 + $localTaxRate), 2)
                : $amount;
            $localTax = round($amount - $netSales, 2);
            $vatExemptSales = round($netSales + $discount, 2);

            return [
                'report_gross_sales' => round($vatExemptSales * (1 + $vatRate), 2),
                'report_net_sales' => $netSales,
                'report_vat_exempt_sales' => $vatExemptSales,
                'report_vat' => 0.0,
                'report_local_tax' => $localTax,
            ];
        }

        $estimatedNetSales = round($amount / (1 + $vatRate + $localTaxRate), 2);
        $localTax = round($estimatedNetSales * $localTaxRate, 2);
        $vatInclusiveAmount = round($amount - $localTax, 2);
        $netSales = round($vatInclusiveAmount / (1 + $vatRate), 2);

        return [
            'report_gross_sales' => round($vatInclusiveAmount + $discount, 2),
            'report_net_sales' => $netSales,
            'report_vat_exempt_sales' => 0.0,
            'report_vat' => round($vatInclusiveAmount - $netSales, 2),
            'report_local_tax' => $localTax,
        ];
    }
}
