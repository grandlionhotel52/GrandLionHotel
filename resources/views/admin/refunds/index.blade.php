@extends('layouts.admin')

@section('title', 'Refund Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <p class="text-uppercase small fw-bold text-secondary mb-1">Financial operations</p>
        <h1 class="mb-1">Refund Management</h1>
        <p class="text-secondary mb-0">Review, approve, reject, and complete customer refunds.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Pending', $summary['pending'], 'warning'],
        ['Approved', $summary['approved'], 'info'],
        ['Completed', $summary['processed'], 'success'],
        ['Rejected', $summary['rejected'], 'danger'],
    ] as [$label, $value, $tone])
        <div class="col-6 col-lg-3">
            <div class="soft-card p-3 h-100">
                <div class="small text-uppercase fw-bold text-secondary">{{ $label }}</div>
                <div class="fs-3 fw-bold text-{{ $tone }}">{{ $value }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="soft-card p-3 mb-4">
    <form method="GET" action="{{ route('admin.refunds.index') }}" class="row g-2 align-items-end" data-ajax-list-form="#admin_refund_results">
        <div class="col-md-6">
            <label class="form-label">Search</label>
            <input type="search" name="q" value="{{ $keyword }}" class="form-control" placeholder="Refund, booking, customer, or reference">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(['pending', 'approved', 'processed', 'rejected'] as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option === 'processed' ? 'completed' : $option) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <a href="{{ route('admin.refunds.index') }}" class="btn btn-outline-secondary" data-ajax-list-reset>Reset</a>
        </div>
    </form>
</div>

<div id="admin_refund_results" aria-live="polite">
<div class="table-shell">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Request</th>
                    <th>Booking / Guest</th>
                    <th>Original payment</th>
                    <th>Refund</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($refunds as $refund)
                    @php($booking = $refund->payment->booking)
                    <tr>
                        <td class="fw-bold">#{{ $refund->id }}</td>
                        <td>
                            <div class="fw-semibold">Booking #{{ $booking->id }}</div>
                            <div class="small text-secondary">{{ $booking->guestName() }}</div>
                        </td>
                        <td>₱{{ number_format((float) $refund->payment->amount, 2) }}</td>
                        <td>{{ $refund->amount ? '₱'.number_format((float) $refund->amount, 2) : 'Not set' }}</td>
                        <td><span class="badge text-bg-{{ match($refund->status) { 'pending' => 'warning', 'approved' => 'info', 'processed' => 'success', default => 'danger' } }}">{{ ucfirst($refund->status === 'processed' ? 'completed' : $refund->status) }}</span></td>
                        <td>{{ optional($refund->requested_at)->format('M d, Y h:i A') ?? '-' }}</td>
                        <td><a href="{{ route('admin.refunds.show', $refund) }}" class="btn btn-sm btn-outline-primary">Review</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-5">No refund requests matched your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $refunds->links() }}</div>
</div>
@endsection
