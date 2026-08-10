@extends('layouts.admin')

@section('title', 'Refund Request #'.$refund->id)

@section('content')
@php
    $booking = $refund->payment->booking;
    $statusLabel = ucfirst($refund->status === 'processed' ? 'completed' : $refund->status);
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <a href="{{ route('admin.refunds.index') }}" class="small text-decoration-none">&larr; Refund queue</a>
        <h1 class="mt-2 mb-1">Refund Request #{{ $refund->id }}</h1>
        <p class="text-secondary mb-0">Booking #{{ $booking->id }} · {{ $booking->guestName() }}</p>
    </div>
    <span class="badge fs-6 text-bg-{{ match($refund->status) { 'pending' => 'warning', 'approved' => 'info', 'processed' => 'success', default => 'danger' } }}">{{ $statusLabel }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="soft-card p-4 mb-4">
            <h2 class="h5">Refund details</h2>
            <dl class="row mb-0">
                <dt class="col-sm-5">Original payment</dt><dd class="col-sm-7">₱{{ number_format((float) $refund->payment->amount, 2) }}</dd>
                <dt class="col-sm-5">Original method</dt><dd class="col-sm-7">{{ \App\Models\Payment::methodLabel($refund->payment->method) }}</dd>
                @if($refund->payment->provider_payment_id)
                    <dt class="col-sm-5">PayMongo Payment ID</dt><dd class="col-sm-7">{{ $refund->payment->provider_payment_id }}</dd>
                @endif
                <dt class="col-sm-5">Approved refund</dt><dd class="col-sm-7">{{ $refund->amount ? '₱'.number_format((float) $refund->amount, 2) : 'Not set' }}</dd>
                <dt class="col-sm-5">Refund method</dt><dd class="col-sm-7">{{ $refund->refund_method ? \App\Models\Payment::methodLabel($refund->refund_method) : 'Not set' }}</dd>
                <dt class="col-sm-5">Refund reference</dt><dd class="col-sm-7">{{ $refund->transaction_reference ?: 'Not set' }}</dd>
                <dt class="col-sm-5">Handled by</dt><dd class="col-sm-7">{{ $refund->handledByAdmin?->name ?? 'Unassigned' }}</dd>
            </dl>
        </div>
        <div class="soft-card p-4">
            <h2 class="h5">Customer request</h2>
            <p><strong>Reason:</strong><br>{{ $refund->reason ?: 'No reason provided.' }}</p>
            @if($refund->rejection_reason)<p class="text-danger"><strong>Rejection reason:</strong><br>{{ $refund->rejection_reason }}</p>@endif
            @if($refund->notes)<p class="mb-0"><strong>Internal notes:</strong><br>{{ $refund->notes }}</p>@endif
        </div>
    </div>
    <div class="col-lg-5">
        @if($refund->status === 'pending')
            <div class="soft-card p-4 mb-4">
                <h2 class="h5">Approve refund</h2>
                <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}">
                    @csrf @method('PATCH')
                    <label class="form-label">Refund amount</label>
                    <input type="number" name="amount" class="form-control mb-3" min="0.01" max="{{ $refund->payment->amount }}" step="0.01" value="{{ old('amount', $refund->payment->amount) }}" required>
                    <label class="form-label">Refund method</label>
                    <select name="refund_method" class="form-select mb-3" required>
                        @foreach(\App\Models\Payment::allowedMethods() as $method)
                            <option value="{{ $method }}" @selected(old('refund_method', $refund->payment->method) === $method)>{{ \App\Models\Payment::methodLabel($method) }}</option>
                        @endforeach
                    </select>
                    <label class="form-label">Internal notes</label>
                    <textarea name="notes" class="form-control mb-3" rows="3">{{ old('notes') }}</textarea>
                    <button class="btn btn-success w-100" onclick="return confirm('Approve this refund request?')">Approve refund</button>
                </form>
            </div>
            <div class="soft-card p-4">
                <h2 class="h5">Reject request</h2>
                <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}">
                    @csrf @method('PATCH')
                    <label class="form-label">Reason shown to customer</label>
                    <textarea name="rejection_reason" class="form-control mb-3" rows="3" required>{{ old('rejection_reason') }}</textarea>
                    <button class="btn btn-outline-danger w-100" onclick="return confirm('Reject this refund request?')">Reject request</button>
                </form>
            </div>
        @elseif($refund->status === 'approved')
            <div class="soft-card p-4">
                <h2 class="h5">Complete refund</h2>
                <p class="small text-secondary">Only complete this after the money has been returned.</p>
                <form method="POST" action="{{ route('admin.refunds.process', $refund) }}">
                    @csrf @method('PATCH')
                    <label class="form-label">Transaction/reference number</label>
                    <input name="transaction_reference" class="form-control mb-3" maxlength="120" required>
                    <label class="form-label">Completion notes</label>
                    <textarea name="notes" class="form-control mb-3" rows="3">{{ old('notes', $refund->notes) }}</textarea>
                    <button class="btn btn-success w-100" onclick="return confirm('Confirm that this refund was completed?')">Mark refund completed</button>
                </form>
            </div>
        @else
            <div class="soft-card p-4">
                <h2 class="h5">Final status</h2>
                <p class="mb-0">This request is {{ strtolower($statusLabel) }} and cannot be changed further.</p>
            </div>
        @endif
    </div>
</div>
@endsection
