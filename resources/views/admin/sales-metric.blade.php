@extends('layouts.admin')

@section('title', $metricLabel.' Breakdown')

@push('head')
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }
        .sales-metric-shell {
            border: 1px solid var(--admin-line);
            border-radius: 14px;
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .sales-metric-total {
            color: #078443;
            font-size: 1.65rem;
            font-weight: 800;
        }
        .sales-metric-table th {
            color: #5a6a80;
            font-size: 0.7rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .sales-metric-table td {
            vertical-align: middle;
            white-space: nowrap;
        }
        @media print {
            body {
                background: #fff !important;
                font-size: 9pt;
            }
            body > nav,
            .sales-metric-actions,
            .sales-metric-receipt {
                display: none !important;
            }
            main.container-xl {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
            }
            .sales-metric-shell {
                border-color: #999 !important;
                box-shadow: none !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .sales-metric-table {
                min-width: 0 !important;
                table-layout: fixed;
                width: 100% !important;
            }
            .sales-metric-table th,
            .sales-metric-table td {
                font-size: 7.5pt !important;
                padding: 0.3rem !important;
                white-space: normal !important;
                overflow-wrap: anywhere;
            }
        }
    </style>
@endpush

@section('content')
    <section class="mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <p class="small text-secondary mb-1">The Grand Lion Hotel · {{ $selectedRangeLabel }}</p>
                <h1 class="h4 mb-1">{{ $metricLabel }} Breakdown</h1>
                <p class="sales-metric-total mb-0">
                    @if($metricIsCount)
                        {{ number_format((int) $metricTotal) }}
                    @else
                        &#8369;{{ number_format((float) $metricTotal, 2) }}
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2 sales-metric-actions">
                <a href="{{ route('admin.sales-report', ['from' => $from, 'to' => $to, 'method' => $method]) }}" class="btn btn-ta-outline">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to Sales Report
                </a>
                <button type="button" class="btn btn-ta" onclick="window.print()">
                    <i class="bi bi-printer me-1" aria-hidden="true"></i>Print Landscape
                </button>
            </div>
        </div>

        <div class="sales-metric-shell p-2 p-lg-3">
            <div class="table-responsive">
                <table class="table sales-metric-table align-middle mb-0">
                    <caption class="visually-hidden">Transactions contributing to {{ $metricLabel }}</caption>
                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Paid At</th>
                            <th>Method</th>
                            <th>Assigned Staff</th>
                            <th class="text-end">Sale Amount</th>
                            <th class="text-end">{{ $metricLabel }}</th>
                            <th class="text-end sales-metric-receipt">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td>#{{ $sale->booking_id }}</td>
                                <td>{{ \Carbon\Carbon::parse($sale->paid_at)->format('M d, Y h:i A') }}</td>
                                <td>{{ \App\Models\Payment::methodLabel((string) $sale->method) }}</td>
                                <td>{{ filled($sale->assigned_staff_name) ? $sale->assigned_staff_name : 'Unassigned' }}</td>
                                <td class="text-end">&#8369;{{ number_format((float) $sale->amount, 2) }}</td>
                                <td class="text-end fw-bold">
                                    @if($metricIsCount)
                                        Included
                                    @else
                                        &#8369;{{ number_format((float) $sale->metric_value, 2) }}
                                    @endif
                                </td>
                                <td class="text-end sales-metric-receipt">
                                    <a href="{{ route('admin.sales-report.receipt', $sale->payment_id) }}" class="btn btn-sm btn-ta-outline" target="_blank" rel="noopener">
                                        <i class="bi bi-printer me-1" aria-hidden="true"></i>View / Print
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">No transactions contribute to this total.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
