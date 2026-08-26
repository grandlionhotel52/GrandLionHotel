@extends('layouts.admin')

@section('title', 'Booking Details')

@push('head')
    <style>
        .booking-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 0.7rem;
        }
        .booking-admin-item {
            border: 1px solid #e1e8f2;
            border-radius: 12px;
            background: #f8fbff;
            padding: 0.65rem 0.72rem;
        }
        .booking-admin-label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }
        .booking-admin-value {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 700;
            color: #1a2738;
            word-break: break-word;
        }
        .booking-admin-note {
            border: 1px dashed #d2deec;
            border-radius: 12px;
            background: #fbfdff;
            padding: 0.7rem 0.75rem;
            margin-top: 0.75rem;
        }
        .booking-admin-head {
            border-radius: 16px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
            padding: 1rem 1.1rem;
        }
    </style>
@endpush

@section('content')
    @php
        $reservationMeta = $booking->reservation_meta ?? [];
        $displayName = $booking->user?->name ?? ($reservationMeta['customer_name'] ?? '-');
        $displayEmail = $booking->user?->email ?? ($reservationMeta['customer_email'] ?? $reservationMeta['contact_email'] ?? '-');
        $displayPhone = $booking->user?->phone ?? ($reservationMeta['customer_phone'] ?? $reservationMeta['contact_phone'] ?? '-');
        $profileAddress = trim(collect([
            $booking->user?->address_line ?? ($reservationMeta['street_address'] ?? null),
            $reservationMeta['street_address_line_2'] ?? null,
            $booking->user?->city ?? ($reservationMeta['guest_city'] ?? null),
            $booking->user?->country,
            $reservationMeta['state_province'] ?? null,
            $reservationMeta['postal_code'] ?? null,
        ])->filter()->implode(', '));
        $paymentProofPath = trim((string) ($booking->payment?->payment_proof_path ?? ''));
        $paymentProofUrl = $paymentProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($paymentProofPath)
            : '';
        $refundMethodLabel = $booking->payment
            ? \App\Models\Payment::methodLabel((string) $booking->payment->method)
            : null;
        $isOnlineAwaitingVerification = $booking->payment_status === 'pending_verification'
            && \App\Models\Payment::isOnlineMethod((string) ($booking->payment?->method ?? ''));
        $statusBadgeClass = match ($booking->status) {
            'confirmed', 'completed' => 'text-bg-success',
            'cancelled' => 'text-bg-danger',
            default => 'text-bg-warning',
        };
        $paymentBadgeClass = match ($booking->payment_status) {
            'paid' => 'text-bg-success',
            'pending_verification', 'refund_pending' => 'text-bg-info',
            default => 'text-bg-warning',
        };
    @endphp

    <div class="booking-admin-head d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <p class="small text-secondary mb-1">Booking #{{ $booking->id }}</p>
            <h1 class="h4 mb-2">{{ $displayName }} · {{ $booking->room->name ?? 'Room unavailable' }}</h1>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge {{ $statusBadgeClass }}">{{ \App\Models\Booking::statusLabel($booking->status) }}</span>
                <span class="badge {{ $paymentBadgeClass }}">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span>
            </div>
            @if($booking->status === 'confirmed' && $booking->payment_status === 'unpaid' && $booking->payment_due_at)
                <p class="small text-danger fw-semibold mt-2 mb-0">Payment due {{ $booking->payment_due_at->format('M d, Y h:i A') }} — auto-cancels when overdue.</p>
            @endif
        </div>
        <x-back-button :href="route('admin.bookings.index')" label="Back to bookings" />
    </div>

    <section class="soft-card p-4 mb-3">
        <div class="booking-admin-grid">
            <div class="booking-admin-item">
                <p class="booking-admin-label">Guest</p>
                <p class="booking-admin-value">{{ $displayName }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Email</p>
                <p class="booking-admin-value">{{ $displayEmail }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Phone</p>
                <p class="booking-admin-value">{{ $displayPhone }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Room</p>
                <p class="booking-admin-value">{{ $booking->room->name ?? '-' }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Stay Dates</p>
                <p class="booking-admin-value">{{ $booking->check_in->format('M d, Y') }} to {{ $booking->check_out->format('M d, Y') }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Guests</p>
                <p class="booking-admin-value">{{ $booking->guests }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Total</p>
                <p class="booking-admin-value">PHP {{ number_format($booking->total_price, 2) }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Payment</p>
                <p class="booking-admin-value">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Checked In</p>
                <p class="booking-admin-value">{{ optional($booking->actual_check_in_at)->format('M d, Y h:i A') ?? '-' }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Checked Out</p>
                <p class="booking-admin-value">{{ optional($booking->actual_check_out_at)->format('M d, Y h:i A') ?? '-' }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Assigned Staff</p>
                <p class="booking-admin-value">{{ $booking->assignedStaff->name ?? '-' }}</p>
            </div>
            <div class="booking-admin-item" style="grid-column: 1 / -1;">
                <p class="booking-admin-label">Address</p>
                <p class="booking-admin-value">{{ $profileAddress ?: '-' }}</p>
            </div>
            <div class="booking-admin-item">
                <p class="booking-admin-label">Meal Option</p>
                <p class="booking-admin-value">{{ ($reservationMeta['meal_plan'] ?? 'room_only') === 'breakfast_included' ? 'Breakfast Included' : 'Room Only — No Breakfast' }}</p>
            </div>
        </div>

        @if($booking->notes)
            <div class="booking-admin-note"><strong>Guest Notes:</strong> {{ $booking->notes }}</div>
        @endif
        @if($booking->staff_notes)
            <div class="booking-admin-note"><strong>Staff Notes:</strong> {{ $booking->staff_notes }}</div>
        @endif
    </section>

    <section class="soft-card p-4 mb-3">
        <h2 class="h5 mb-3">Staff assignment</h2>
        <form method="POST" action="{{ route('admin.bookings.assign-staff', $booking) }}" class="row g-3 align-items-end" data-confirm="Save this staff assignment?">
            @csrf
            @method('PATCH')
            <div class="col-md-5">
                <label class="form-label">Responsible staff</label>
                <select class="form-select" name="staff_id">
                    <option value="">Unassigned</option>
                    @foreach($staffMembers as $staffMember)
                        <option value="{{ $staffMember->id }}" @selected((int) $booking->staff_id === (int) $staffMember->id)>
                            {{ $staffMember->name }} ({{ $staffMember->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-ta w-100">Save assignment</button>
            </div>
            <div class="col-12">
                <p class="small text-secondary mb-0">This staff member owns the booking workflow.</p>
            </div>
        </form>
    </section>

    <section class="soft-card p-4 mb-3">
        <h2 class="h5 mb-3">Booking status</h2>
        <form method="POST" action="{{ route('admin.bookings.update-status', $booking) }}" class="row g-3 align-items-end" data-confirm="Update this booking status?">
            @csrf
            @method('PATCH')
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" required>
                    @foreach(['pending', 'confirmed', 'cancelled', 'completed'] as $status)
                        <option value="{{ $status }}" {{ $booking->status === $status ? 'selected' : '' }}>
                            {{ \App\Models\Booking::statusLabel($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-ta w-100">Update</button>
            </div>
        </form>
    </section>

    @if($booking->payment)
        <section class="soft-card p-4">
            <h2 class="h5 mb-3">Payment Details</h2>
            @if($isOnlineAwaitingVerification)
                <div class="alert alert-info small">
                    Verify the submitted reference and proof before approving.
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <form method="POST" action="{{ route('admin.bookings.approve-online-payment', $booking) }}" data-confirm="Approve this online payment submission?">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-ta">Approve Online Payment</button>
                    </form>
                    <form method="POST" action="{{ route('admin.bookings.reject-online-payment', $booking) }}" data-confirm="Reject this online payment submission?">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-ta-outline">Reject Submission</button>
                    </form>
                </div>
            @endif
            <div class="row g-2">
                <div class="col-md-6"><strong>Method:</strong> {{ \App\Models\Payment::methodLabel($booking->payment->method) }}</div>
                <div class="col-md-6"><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $booking->payment->status)) }}</div>
                @if($booking->payment_status === 'refund_pending' && $refundMethodLabel)
                    <div class="col-md-6"><strong>Refund Method:</strong> {{ $refundMethodLabel }}</div>
                @endif
                @if(filled($booking->payment->customer_reference))
                    <div class="col-md-6"><strong>Customer Ref No.:</strong> {{ $booking->payment->customer_reference }}</div>
                @endif
                @if(filled($booking->payment->qr_reference))
                    <div class="col-md-6"><strong>QR Reference:</strong> {{ $booking->payment->qr_reference }}</div>
                @endif
                @if($paymentProofUrl !== '')
                    <div class="col-md-6"><strong>Uploaded Proof:</strong> <a href="{{ $paymentProofUrl }}" target="_blank" rel="noopener">View screenshot</a></div>
                @endif
                <div class="col-md-6"><strong>Amount:</strong> PHP {{ number_format($booking->payment->amount, 2) }}</div>
                <div class="col-md-6"><strong>Paid At:</strong> {{ optional($booking->payment->paid_at)->format('M d, Y h:i A') ?? '-' }}</div>
                <div class="col-md-6"><strong>Verified At:</strong> {{ optional($booking->payment->verified_at)->format('M d, Y h:i A') ?? 'Pending verification' }}</div>
                <div class="col-md-6"><strong>Verified By:</strong> {{ $booking->payment->verified_at ? ($booking->payment->source === 'paymongo_checkout' ? 'PayMongo' : 'Hotel staff') : '-' }}</div>
                <div class="col-12"><strong>Transaction Ref:</strong> {{ $booking->payment->transaction_reference ?? '-' }}</div>
                @if($booking->payment->provider_payment_id)
                    <div class="col-12"><strong>PayMongo Payment ID:</strong> {{ $booking->payment->provider_payment_id }}</div>
                @endif
            </div>
            @if($booking->payment_status === 'refund_pending' && $refundMethodLabel)
                <div class="booking-admin-note">
                    <strong>Refund Rule:</strong> Refund should be returned using the original payment method: {{ $refundMethodLabel }}.
                </div>
            @endif

            @if($booking->status === 'cancelled' && $booking->payment_status === 'paid' && (!$booking->latestRefundRequest || $booking->latestRefundRequest->status === 'rejected'))
                <div class="booking-admin-note mt-3">
                    <h3 class="h6">Optional refund for cancelled/no-show booking</h3>
                    <p class="small text-secondary">Use this only when management decides to make an exception. Creating the request does not send money yet.</p>
                    <form method="POST" action="{{ route('admin.bookings.create-refund-request', $booking) }}" class="row g-3" data-confirm="Create this refund request for admin review?">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Refund amount</label>
                            <input type="number" name="refund_amount" class="form-control" min="0.01" max="{{ $booking->payment->amount }}" step="0.01" value="{{ old('refund_amount', $booking->payment->amount) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Reason for exception</label>
                            <input name="refund_reason" class="form-control" maxlength="1000" placeholder="Example: Approved medical emergency" value="{{ old('refund_reason') }}" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning">Create refund request</button>
                        </div>
                    </form>
                </div>
            @elseif($booking->latestRefundRequest && $booking->latestRefundRequest->status !== 'rejected')
                <div class="booking-admin-note mt-3">
                    <strong>Refund:</strong> This booking already has a {{ $booking->latestRefundRequest->status }} refund request.
                    <a href="{{ route('admin.refunds.show', $booking->latestRefundRequest) }}">Open refund request</a>
                </div>
            @endif
        </section>
    @endif
@endsection
