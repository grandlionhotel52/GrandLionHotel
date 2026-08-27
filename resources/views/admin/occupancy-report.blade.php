@extends('layouts.admin')

@section('title', 'Occupancy Report')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Occupancy Report</h1>
            <p class="text-secondary mb-0">Confirmed and completed room nights, including both cash and online payments.</p>
        </div>
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
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Overall occupancy', 'value' => number_format($summary['occupancy_rate'], 1).'%'],
            ['label' => 'Room nights sold', 'value' => number_format($summary['room_nights_sold'])],
            ['label' => 'Room nights available', 'value' => number_format($summary['room_nights_available'])],
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

    <section class="table-shell">
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
