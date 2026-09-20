@extends('layouts.admin')

@section('title', 'Occupancy Report')

@push('head')
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        .admin-report-heading .hotel-name {
            color: var(--admin-brand-dark);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body {
                background: #fff !important;
                font-size: 8.5pt;
            }
            body > nav,
            .skip-link,
            .flash-stack,
            .ux-back-to-top,
            .admin-report-controls,
            .admin-report-print-button {
                display: none !important;
            }
            main.container-xl {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
            }
            .admin-report-heading {
                display: block !important;
                text-align: center;
                margin-bottom: 8mm !important;
            }
            .admin-report-heading h1 {
                font-size: 18pt !important;
            }
            .admin-report-heading p {
                margin-bottom: 0 !important;
            }
            .admin-report-print-meta {
                display: block !important;
                margin-top: 2mm;
                font-size: 8pt;
                color: #333 !important;
            }
            .admin-report-metrics {
                margin-bottom: 5mm !important;
            }
            .admin-report-metrics > [class*="col-"] {
                flex: 0 0 25%;
                max-width: 25%;
            }
            .soft-card,
            .table-shell {
                border-color: #999 !important;
                box-shadow: none !important;
            }
            .soft-card {
                break-inside: avoid;
            }
            .table-shell {
                overflow: visible !important;
                break-inside: auto;
                margin-bottom: 5mm !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .table {
                width: 100% !important;
                table-layout: fixed;
            }
            .table thead th,
            .table tbody td {
                padding: 0.35rem !important;
                font-size: 8pt !important;
                white-space: normal !important;
            }
            .table thead {
                display: table-header-group;
            }
            .table tr,
            .table td,
            .table th {
                break-inside: avoid;
            }
            .progress {
                border: 1px solid #999;
            }
            .admin-daily-occupancy {
                break-before: page;
            }
        }
    </style>
@endpush

@section('content')
    <div class="admin-report-heading d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="hotel-name mb-1">The Grand Lion Hotel</p>
            <h1 class="h3 mb-1">Occupancy Report</h1>
            <p class="text-secondary mb-0">Confirmed and completed room nights, including both cash and online payments.</p>
            <p class="admin-report-print-meta d-none">
                Reporting period: {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}
                &middot; Generated {{ now()->format('M d, Y h:i A') }}
            </p>
        </div>
        <button type="button" class="btn btn-ta-outline admin-report-print-button" onclick="window.print()">
            <i class="bi bi-printer me-1" aria-hidden="true"></i>Print Landscape
        </button>
    </div>

    <section class="soft-card admin-report-controls p-3 mb-4">
        <form method="GET" action="{{ route('admin.occupancy-report') }}" class="d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label small">From</label>
                <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>
            <div>
                <label class="form-label small">To</label>
                <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>
            <button class="btn btn-ta" type="submit">Apply</button>
        </form>
    </section>

    <div class="row g-3 mb-4 admin-report-metrics">
        @foreach([
            ['label' => 'Overall occupancy', 'value' => number_format($summary['occupancy_rate'], 1).'%'],
            ['label' => 'Room nights sold', 'value' => number_format($summary['room_nights_sold'])],
            ['label' => 'Rooms with no sales', 'value' => number_format($summary['rooms_without_sales'])],
            ['label' => 'Hotel rooms', 'value' => number_format($summary['rooms'])],
        ] as $metric)
            <div class="col-sm-6 col-xl-3">
                <section class="soft-card p-3 h-100">
                    <p class="small text-secondary text-uppercase fw-bold mb-1">{{ $metric['label'] }}</p>
                    <p class="h3 mb-0">{{ $metric['value'] }}</p>
                </section>
            </div>
        @endforeach
    </div>

    <section class="table-shell mb-4">
        <div class="px-3 pt-3">
            <h2 class="h5 mb-1">Room-by-Room Occupancy</h2>
            <p class="small text-secondary mb-2">Rooms with no sold nights are listed first for {{ \Carbon\Carbon::parse($from)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M d, Y') }}.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Type</th>
                        <th>View</th>
                        <th>Sold Nights</th>
                        <th>Unsold Nights</th>
                        <th>Occupancy</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roomOccupancy as $room)
                        <tr class="{{ $room->sales_status === 'Unsold' ? 'table-danger' : '' }}">
                            <td class="fw-semibold">{{ $room->name }}</td>
                            <td>{{ $room->type }}</td>
                            <td>{{ $room->view_type ?: '—' }}</td>
                            <td>{{ number_format($room->sold_nights) }}</td>
                            <td>{{ number_format($room->unsold_nights) }}</td>
                            <td>{{ number_format($room->occupancy_rate, 1) }}%</td>
                            <td>
                                <span class="badge {{ $room->sales_status === 'Unsold' ? 'text-bg-danger' : ($room->sales_status === 'Fully occupied' ? 'text-bg-success' : 'text-bg-warning') }}">
                                    {{ $room->sales_status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No rooms found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="table-shell admin-daily-occupancy">
        <div class="px-3 pt-3">
            <h2 class="h5 mb-1">Daily Occupancy</h2>
            <p class="small text-secondary mb-2">Hotel-wide occupied and available room totals by date.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Occupied</th>
                        <th>Available</th>
                        <th>Occupancy</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyOccupancy as $day)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                            <td>{{ $day->occupied_rooms }}</td>
                            <td>{{ $day->available_rooms }}</td>
                            <td style="min-width: 190px">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px">
                                        <div class="progress-bar" style="width: {{ min(100, $day->occupancy_rate) }}%"></div>
                                    </div>
                                    <span class="small fw-bold">{{ number_format($day->occupancy_rate, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-4">No dates in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
