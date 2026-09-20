@extends('layouts.admin')

@section('title', 'Sales Report')

@push('head')
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }
        .admin-report-heading {
            margin-bottom: 1rem;
        }
        .admin-report-heading .hotel-name {
            color: var(--admin-brand-dark);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .admin-report-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #ffffff;
            box-shadow: var(--admin-shadow);
            padding: 0.82rem 0.9rem;
            height: 100%;
        }
        .admin-report-stat-link {
            color: inherit;
            display: block;
            height: 100%;
            text-decoration: none;
        }
        .admin-report-stat-link .admin-report-stat {
            transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }
        .admin-report-stat-link:hover .admin-report-stat,
        .admin-report-stat-link:focus-visible .admin-report-stat {
            border-color: var(--admin-brand);
            box-shadow: 0 12px 28px rgba(63, 44, 21, 0.16);
            transform: translateY(-2px);
        }
        .admin-report-stat-link:focus-visible {
            border-radius: 14px;
            outline: 3px solid rgba(184, 146, 84, 0.28);
            outline-offset: 2px;
        }
        .admin-report-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .admin-report-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-report-tax-danger {
            color: #b42318 !important;
        }
        .admin-report-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .admin-report-table thead th {
            font-size: 0.7rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #5a6a80;
            white-space: nowrap;
        }
        .admin-report-table tbody td {
            vertical-align: middle;
            border-color: #edf2f7;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow-wrap: normal;
        }
        .admin-report-table {
            width: 100%;
        }
        .admin-report-table--daily {
            min-width: 960px;
        }
        .admin-report-table--summary {
            min-width: 640px;
        }
        .admin-report-table--transactions {
            min-width: 760px;
        }
        .admin-report-shell .table-responsive {
            overflow-y: hidden;
        }
        @media print {
            body {
                background: #fff !important;
                font-size: 9pt;
            }
            body > nav,
            .admin-report-controls,
            .admin-report-print-button,
            .admin-report-actions {
                display: none !important;
            }
            main.container-xl {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
            }
            .admin-report-heading {
                text-align: center;
                margin-bottom: 8mm;
            }
            .admin-report-heading h1 {
                font-size: 18pt !important;
            }
            .admin-report-stat,
            .admin-report-shell {
                border-color: #999 !important;
                box-shadow: none !important;
                break-inside: avoid;
            }
            .admin-report-metrics > [class*="col-"] {
                flex: 0 0 50%;
                max-width: 50%;
            }
            .admin-report-shell .table-responsive {
                overflow: visible !important;
            }
            .admin-report-table {
                width: 100% !important;
                min-width: 0 !important;
                table-layout: fixed;
            }
            .admin-report-table thead th,
            .admin-report-table tbody td {
                padding: 0.3rem !important;
                font-size: 7pt !important;
                white-space: normal !important;
                overflow-wrap: anywhere;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $methodLabel = static function (string $value): string {
            return \App\Models\Payment::methodLabel($value);
        };
        $metricQuery = ['from' => $from, 'to' => $to, 'method' => $method];
    @endphp

    <section class="mb-4">
        <div class="admin-report-heading d-flex flex-wrap align-items-end justify-content-between gap-2">
            <div>
                <p class="hotel-name mb-1">The Grand Lion Hotel</p>
                <h1 class="h4 mb-1">Sales Report</h1>
                <p class="text-secondary mb-0" id="admin_sales_range_label" data-ajax-list-sync>Paid sales for {{ $selectedRangeLabel }}</p>
            </div>
            <button type="button" class="btn btn-ta-outline admin-report-print-button" onclick="window.print()">
                <i class="bi bi-printer me-1" aria-hidden="true"></i>Print Landscape
            </button>
        </div>

        <section class="admin-report-shell admin-report-controls p-3 p-lg-4 mb-4">
            <form method="GET" action="{{ route('admin.sales-report') }}" data-ajax-list-form="#admin_sales_results">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from" class="form-control" value="{{ $from }}">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to" class="form-control" value="{{ $to }}">
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label">Payment Method</label>
                        <select name="method" class="form-select">
                            <option value="all" @selected($method === 'all')>All methods</option>
                            <option value="cash" @selected($method === 'cash')>Cash</option>
                            <option value="credit_debit_card" @selected($method === 'credit_debit_card')>Credit/Debit Card via PayMongo</option>
                            <option value="gcash" @selected($method === 'gcash')>GCash via PayMongo</option>
                            <option value="paymaya" @selected($method === 'paymaya')>Maya via PayMongo</option>
                            <option value="qrph" @selected($method === 'qrph')>QR Ph via PayMongo</option>
                            <option value="instapay" @selected($method === 'instapay')>Legacy InstaPay</option>
                        </select>
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <a href="{{ route('admin.sales-report') }}" class="btn btn-ta-outline" data-ajax-list-reset>Reset</a>
                        <a href="{{ route('admin.sales-report.export', ['from' => $from, 'to' => $to, 'method' => $method]) }}" class="btn btn-ta">
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </form>
        </section>

        <div id="admin_sales_results" aria-live="polite">
        <div class="row g-3 mb-4 admin-report-metrics">
            <div class="col-sm-6 col-xl-2">
                <a href="{{ route('admin.sales-report.metric', array_merge(['metric' => 'total-sales'], $metricQuery)) }}" class="admin-report-stat-link" aria-label="View Total Sales breakdown">
                    <div class="admin-report-stat">
                        <p class="label">Total Sales</p>
                        <p class="value text-success">&#8369;{{ number_format((float) $summary['gross_revenue'], 2) }}</p>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-2">
                <a href="{{ route('admin.sales-report.metric', array_merge(['metric' => 'room-sales'], $metricQuery)) }}" class="admin-report-stat-link" aria-label="View Room Sales breakdown">
                    <div class="admin-report-stat">
                        <p class="label">Room Sales</p>
                        <p class="value">&#8369;{{ number_format((float) $summary['room_sales'], 2) }}</p>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-2">
                <a href="{{ route('admin.sales-report.metric', array_merge(['metric' => 'food-sales'], $metricQuery)) }}" class="admin-report-stat-link" aria-label="View Food Sales breakdown">
                    <div class="admin-report-stat">
                        <p class="label">Food Sales</p>
                        <p class="value">&#8369;{{ number_format((float) $summary['food_sales'], 2) }}</p>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-2">
                <a href="{{ route('admin.sales-report.metric', array_merge(['metric' => 'paid-bookings'], $metricQuery)) }}" class="admin-report-stat-link" aria-label="View Paid Bookings breakdown">
                    <div class="admin-report-stat">
                        <p class="label">Paid Bookings</p>
                        <p class="value text-primary">{{ $summary['paid_bookings'] }}</p>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-2">
                <a href="{{ route('admin.sales-report.metric', array_merge(['metric' => 'discount-total'], $metricQuery)) }}" class="admin-report-stat-link" aria-label="View Discount Total breakdown">
                    <div class="admin-report-stat">
                        <p class="label">Discount Total</p>
                        <p class="value text-warning">&#8369;{{ number_format((float) $summary['total_discount'], 2) }}</p>
                    </div>
                </a>
            </div>
        </div>
        <div class="row g-3 mb-4 admin-report-metrics">
            @foreach([
                ['metric' => 'vat-exempt-sales', 'label' => 'VAT-Exempt Sales', 'value' => $summary['vat_exempt_sales'], 'label_class' => 'admin-report-tax-danger', 'value_class' => 'admin-report-tax-danger'],
                ['metric' => 'vat', 'label' => 'VAT (12/112)', 'value' => $summary['vat_total'], 'label_class' => 'admin-report-tax-danger', 'value_class' => 'admin-report-tax-danger'],
                ['metric' => 'local-tax', 'label' => 'Local Tax (5%)', 'value' => $summary['local_tax_total'], 'label_class' => 'admin-report-tax-danger', 'value_class' => 'admin-report-tax-danger'],
                ['metric' => 'net-sales', 'label' => 'Net Sales', 'value' => $summary['net_sales_excluding_vat'], 'label_class' => '', 'value_class' => 'text-primary'],
            ] as $taxMetric)
                <div class="col-sm-6 col-xl-3">
                    <a href="{{ route('admin.sales-report.metric', array_merge(['metric' => $taxMetric['metric']], $metricQuery)) }}" class="admin-report-stat-link" aria-label="View {{ $taxMetric['label'] }} breakdown">
                        <div class="admin-report-stat">
                            <p class="label {{ $taxMetric['label_class'] }}">{{ $taxMetric['label'] }}</p>
                            <p class="value {{ $taxMetric['value_class'] }}">&#8369;{{ number_format((float) $taxMetric['value'], 2) }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        </div>
    </section>

    <div id="admin_sales_detail_results" data-ajax-list-sync>
    <section class="row g-4 mb-4">
        <div class="col-12">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Daily Sales</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table admin-report-table--daily align-middle mb-0">
                        <caption class="visually-hidden">Paid sales grouped by day</caption>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Paid Bookings</th>
                                <th>Discount Total</th>
                                <th class="text-end">Net Sales</th>
                                <th class="text-end">VAT</th>
                                <th class="text-end">Local Tax</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailySales as $day)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</td>
                                    <td>{{ $day->paid_bookings }}</td>
                                    <td>&#8369;{{ number_format((float) $day->discount_total, 2) }}</td>
                                    <td class="text-end">&#8369;{{ number_format((float) $day->net_sales_excluding_vat, 2) }}</td>
                                    <td class="text-end">&#8369;{{ number_format((float) $day->vat_total, 2) }}</td>
                                    <td class="text-end">&#8369;{{ number_format((float) $day->local_tax_total, 2) }}</td>
                                    <td class="text-end">&#8369;{{ number_format((float) $day->gross_revenue, 2) }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $day->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-secondary">No paid sales found for this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Payment Methods</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table admin-report-table--summary align-middle mb-0">
                        <caption class="visually-hidden">Paid sales grouped by payment method</caption>
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Paid Bookings</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($methodBreakdown as $row)
                                <tr>
                                    <td>{{ $methodLabel((string) $row->method) }}</td>
                                    <td>{{ $row->paid_bookings }}</td>
                                    <td class="text-end">&#8369;{{ number_format((float) $row->gross_revenue, 2) }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $row->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">No payment method totals yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Staff Performance</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table admin-report-table--summary align-middle mb-0">
                        <caption class="visually-hidden">Paid sales attributed to staff</caption>
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Paid Bookings</th>
                                <th class="text-end">Gross</th>
                                <th class="text-end">Collected</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffBreakdown as $row)
                                <tr>
                                    <td>{{ $row->staff_name }}</td>
                                    <td>{{ $row->paid_bookings }}</td>
                                    <td class="text-end">&#8369;{{ number_format((float) $row->gross_revenue, 2) }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $row->revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-secondary">No staff sales data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="admin-report-shell p-2 p-lg-3 h-100">
                <div class="d-flex justify-content-between align-items-center px-2 pt-1 mb-2">
                    <h2 class="h5 mb-0">Paid Transactions</h2>
                </div>
                <div class="table-responsive">
                    <table class="table admin-report-table admin-report-table--transactions align-middle mb-0">
                        <caption class="visually-hidden">All paid transactions matching the selected filters</caption>
                        <thead>
                            <tr>
                                <th>Paid At</th>
                                <th>Method</th>
                                <th>Guest Care Staff</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end admin-report-actions">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($sale->paid_at)->format('M d, Y h:i A') }}</td>
                                    <td>{{ $methodLabel((string) $sale->method) }}</td>
                                    <td>{{ trim((string) ($sale->assigned_staff_name ?? '')) !== '' ? $sale->assigned_staff_name : 'Not recorded' }}</td>
                                    <td class="text-end fw-semibold">&#8369;{{ number_format((float) $sale->amount, 2) }}</td>
                                    <td class="text-end admin-report-actions">
                                        <a
                                            href="{{ route('admin.sales-report.receipt', $sale->payment_id) }}"
                                            class="btn btn-sm btn-ta-outline"
                                            target="_blank"
                                            rel="noopener"
                                            aria-label="View and print receipt for sale {{ $sale->payment_id }}"
                                        >
                                            <i class="bi bi-printer me-1" aria-hidden="true"></i>View / Print
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">No paid transactions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    </div>
@endsection
