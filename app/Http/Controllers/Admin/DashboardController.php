<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\RefundRequest;
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
            ->leftJoin('staff', 'staff.staff_id', '=', 'bookings.staff_id')
            ->whereIn('payments.status', ['paid', 'refunded'])
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
                'payments.discount_amount',
                'payments.paid_at',
                'bookings.staff_id as assigned_staff_id',
                'staff.name as assigned_staff_name',
            ])
            ->orderByDesc('payments.paid_at')
            ->get();

        $grossRevenue = (float) $payments->sum(static fn ($payment): float => (float) $payment->amount);
        $refundedTotal = (float) RefundRequest::query()
            ->where('status', RefundRequest::STATUS_PROCESSED)
            ->whereDate('processed_at', '>=', $from)
            ->whereDate('processed_at', '<=', $to)
            ->sum('amount');
        $totalRevenue = $grossRevenue - $refundedTotal;
        $paidBookings = $payments->count();

        $summary = [
            'total_revenue' => $totalRevenue,
            'gross_revenue' => $grossRevenue,
            'refunded_total' => $refundedTotal,
            'paid_bookings' => $paidBookings,
            'average_sale' => $paidBookings > 0 ? round($totalRevenue / $paidBookings, 2) : 0.0,
            'total_discount' => (float) $payments->sum(static fn ($payment): float => (float) ($payment->discount_amount ?? 0)),
            'online_payments' => $payments->filter(static fn ($payment): bool => Payment::isOnlineMethod((string) $payment->method))->count(),
            'cash_payments' => $payments->where('method', Payment::METHOD_CASH)->count(),
        ];

        $dailySales = $payments
            ->groupBy(static fn ($payment): string => Carbon::parse($payment->paid_at)->toDateString())
            ->map(static function ($rows, string $date): object {
                return (object) [
                    'date' => $date,
                    'paid_bookings' => $rows->count(),
                    'revenue' => (float) $rows->sum(static fn ($payment): float => (float) $payment->amount),
                    'discount_total' => (float) $rows->sum(static fn ($payment): float => (float) ($payment->discount_amount ?? 0)),
                ];
            })
            ->sortByDesc(static fn (object $row): string => $row->date)
            ->values();

        $methodBreakdown = $payments
            ->groupBy(static fn ($payment): string => (string) $payment->method)
            ->map(static function ($rows, string $methodName): object {
                return (object) [
                    'method' => $methodName,
                    'paid_bookings' => $rows->count(),
                    'revenue' => (float) $rows->sum(static fn ($payment): float => (float) $payment->amount),
                ];
            })
            ->sortByDesc(static fn (object $row): float => $row->revenue)
            ->values();

        $staffBreakdown = $payments
            ->groupBy(static function ($payment): string {
                $staffId = (int) ($payment->assigned_staff_id ?? 0);
                $staffName = trim((string) ($payment->assigned_staff_name ?? ''));

                return $staffId > 0 ? 'staff:'.$staffId : 'staff:unassigned:'.($staffName !== '' ? $staffName : 'Unassigned');
            })
            ->map(static function ($rows): object {
                $first = $rows->first();
                $staffId = (int) ($first->assigned_staff_id ?? 0);
                $staffName = trim((string) ($first->assigned_staff_name ?? ''));

                return (object) [
                    'staff_id' => $staffId > 0 ? $staffId : null,
                    'staff_name' => $staffName !== '' ? $staffName : 'Unassigned',
                    'paid_bookings' => $rows->count(),
                    'revenue' => (float) $rows->sum(static fn ($payment): float => (float) $payment->amount),
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
}
