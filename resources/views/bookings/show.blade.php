@extends('layouts.app')

@section('title', 'Booking Details')

@push('head')
    <style>
        .booking-flow {
            border-radius: 18px;
            border: 1px solid var(--line);
            background: #fff;
            padding: 1rem;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }
        .booking-flow-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.55rem;
        }
        .booking-flow-step {
            border-radius: 12px;
            border: 1px solid #e7dccb;
            background: #fff;
            padding: 0.62rem 0.7rem;
            min-height: 72px;
        }
        .booking-flow-step .label {
            font-size: 0.74rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 800;
            margin-bottom: 0.18rem;
        }
        .booking-flow-step .status {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1f2937;
        }
        .booking-flow-step.is-complete {
            border-color: rgba(6, 118, 71, 0.35);
            background: rgba(236, 247, 240, 0.96);
        }
        .booking-flow-step.is-current {
            border-color: rgba(184, 146, 84, 0.45);
            background: rgba(250, 245, 235, 0.96);
        }
        .booking-flow-step.is-cancelled {
            border-color: rgba(180, 35, 24, 0.35);
            background: rgba(254, 244, 244, 0.96);
        }
        .booking-hero {
            display: grid;
            grid-template-columns: minmax(180px, 260px) 1fr;
            overflow: hidden;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.09);
        }
        .booking-hero-image {
            width: 100%;
            height: 100%;
            min-height: 190px;
            object-fit: cover;
        }
        .booking-hero-content {
            padding: clamp(1.25rem, 3vw, 2rem);
        }
        .booking-status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            background: #f3f4f6;
            color: #374151;
        }
        .booking-status-chip.success {
            background: #eaf7ef;
            color: #067647;
        }
        .booking-status-chip.warning {
            background: #fff4dd;
            color: #8a5a00;
        }
        .booking-status-chip.danger {
            background: #fdecec;
            color: #b42318;
        }
        .booking-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin: 0;
        }
        .booking-detail-grid > div {
            width: auto;
            border: 1px solid #eee4d5;
            border-radius: 12px;
            background: #fffdf9;
            padding: 0.75rem 0.85rem;
        }
        .booking-side-panel {
            position: sticky;
            top: 88px;
        }
        .booking-next-card {
            border-radius: 16px;
            border: 1px dashed rgba(184, 146, 84, 0.52);
            background: rgba(184, 146, 84, 0.08);
            padding: 0.8rem 0.85rem;
        }
        @media (max-width: 991.98px) {
            .booking-flow-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .booking-side-panel {
                position: static;
            }
        }
        @media (max-width: 575.98px) {
            .booking-flow-grid {
                grid-template-columns: 1fr;
            }
            .booking-hero {
                grid-template-columns: 1fr;
            }
            .booking-hero-image {
                height: 210px;
                min-height: 0;
            }
            .booking-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $reservationMeta = $booking->reservation_meta ?? [];
        $discountProofPath = (string) data_get($reservationMeta, 'discount_id_photo_path', '');
        $discountProofUrl = $discountProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($discountProofPath)
            : '';
        $paymentProofPath = trim((string) ($booking->payment?->payment_proof_path ?? ''));
        $paymentProofUrl = $paymentProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($paymentProofPath)
            : '';

        $isCancelled = $booking->status === 'cancelled';
        $isRequested = true;
        $isConfirmed = in_array($booking->status, ['confirmed', 'completed'], true);
        $canRequestReschedule = $booking->canRequestReschedule();
        $hasPendingRescheduleRequest = $booking->hasPendingRescheduleRequest();
        $canRequestRoomTransfer = $booking->canRequestRoomTransfer();
        $hasPendingRoomTransferRequest = $booking->hasPendingRoomTransferRequest();
        $isPaid = $booking->payment_status === 'paid';
        $isRefundPending = $booking->payment_status === 'refund_pending';
        $latestRefundRequest = $booking->latestRefundRequest;
        $refundStatusLabel = $latestRefundRequest
            ? ucfirst(str_replace('_', ' ', (string) $latestRefundRequest->status))
            : null;
        $refundMethodLabel = $booking->payment
            ? \App\Models\Payment::methodLabel((string) $booking->payment->method)
            : null;
        $canRequestPaidRefund = $booking->canBeCancelled() && $booking->payment_status === 'paid';
        $canCancelWithoutRefund = $booking->canBeCancelled() && $booking->payment_status !== 'paid';
        $isCashAwaitingVerification = $booking->status === 'confirmed'
            && $booking->payment_status !== 'paid'
            && strtolower((string) ($booking->payment?->method ?? '')) === 'cash';
        $isOnlineAwaitingVerification = $booking->status === 'confirmed'
            && $booking->payment_status === 'pending_verification'
            && \App\Models\Payment::isOnlineMethod((string) ($booking->payment?->method ?? ''));
        $isCompleted = $booking->status === 'completed';
        $billedUnits = $booking->nights();
        $pricingQuote = $booking->pricingQuote();
        $bookingStatusClass = match ($booking->status) {
            'confirmed', 'completed' => 'success',
            'cancelled' => 'danger',
            default => 'warning',
        };
        $bookingStatusLabel = \App\Models\Booking::statusLabel($booking->status);
        $paymentStatusClass = match ($booking->payment_status) {
            'paid' => 'success',
            'refund_pending' => 'warning',
            default => $isCancelled ? 'danger' : 'warning',
        };
        $requestedReturnTo = (string) request('return_to', '');
        $hasReturnLocation = str_starts_with($requestedReturnTo, '/') && !str_starts_with($requestedReturnTo, '//');
        $backUrl = $hasReturnLocation
            ? $requestedReturnTo
            : route('bookings.my');

        $nextAction = match (true) {
            $isRefundPending => 'Your refund is under review and will be returned through your original payment method'
                .($refundMethodLabel ? ': '.$refundMethodLabel.'.' : '.'),
            $isCancelled => 'This reservation has been cancelled. If you still plan to stay, create a new booking.',
            $booking->status === 'pending' => 'Wait for staff confirmation. Payment becomes available right after approval.',
            $isOnlineAwaitingVerification => 'Your online payment proof was submitted. Please wait for staff to verify your transfer.',
            $isCashAwaitingVerification => 'Cash payment selected. Please pay at front desk and wait for staff confirmation.',
            $booking->status === 'confirmed' && $booking->payment_status !== 'paid' => 'Complete payment to finalize this reservation.',
            $booking->status === 'confirmed' && $booking->payment_status === 'paid' => 'You are all set. Bring your booking reference at check-in.',
            $booking->status === 'completed' => 'Stay completed. You can download your receipt anytime.',
            default => 'Review your reservation details below.',
        };
    @endphp

    <section class="booking-hero mb-4">
        <img
            src="{{ $booking->room?->image_url }}"
            alt="{{ $booking->room?->name ?? 'Reserved room' }}"
            class="booking-hero-image"
        >
        <div class="booking-hero-content">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <p class="ta-eyebrow mb-1">Reservation #{{ $booking->id }}</p>
                    <h1 class="h2 mb-1">{{ $booking->room?->name ?? 'Room reservation' }}</h1>
                    <p class="text-secondary mb-3">
                        {{ $booking->check_in->format('M d, Y') }} – {{ $booking->check_out->format('M d, Y') }}
                        · {{ $billedUnits }} night{{ $billedUnits === 1 ? '' : 's' }}
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="booking-status-chip {{ $bookingStatusClass }}">
                            <i class="bi bi-calendar-check"></i>{{ $bookingStatusLabel }}
                        </span>
                        <span class="booking-status-chip {{ $paymentStatusClass }}">
                            <i class="bi bi-credit-card"></i>{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}
                        </span>
                    </div>
                    @if($booking->status === 'confirmed' && $booking->payment_status === 'unpaid' && $booking->payment_due_at)
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Payment due:</strong> {{ $booking->payment_due_at->format('M d, Y h:i A') }}.
                            This reservation will be cancelled automatically if payment is not completed before the deadline.
                        </div>
                    @endif
                </div>
                <x-back-button :href="$backUrl" :label="$hasReturnLocation ? 'Back' : 'Back to my bookings'" />
            </div>
        </div>
    </section>

    <section class="booking-flow mb-4">
        <div class="booking-flow-grid">
            <article class="booking-flow-step {{ $isRequested ? 'is-complete' : '' }}">
                <p class="label mb-0">Step 1</p>
                <p class="status mb-0">Pre-booking submitted</p>
            </article>
            <article class="booking-flow-step {{ $isCancelled ? 'is-cancelled' : ($isConfirmed ? 'is-complete' : 'is-current') }}">
                <p class="label mb-0">Step 2</p>
                <p class="status mb-0">{{ $isCancelled ? 'Cancelled' : ($isConfirmed ? 'Confirmed' : 'Not yet confirmed') }}</p>
            </article>
            <article class="booking-flow-step {{ $isCancelled ? 'is-cancelled' : ($isPaid ? 'is-complete' : ($isConfirmed ? 'is-current' : '')) }}">
                <p class="label mb-0">Step 3</p>
                <p class="status mb-0">{{ $isCancelled ? 'Payment closed' : ($isPaid ? 'Payment received' : ($isCashAwaitingVerification ? 'Cash verification' : ($isOnlineAwaitingVerification ? 'Online verification' : 'Pending payment'))) }}</p>
            </article>
            <article class="booking-flow-step {{ $isCancelled ? 'is-cancelled' : ($isCompleted ? 'is-complete' : ($isPaid ? 'is-current' : '')) }}">
                <p class="label mb-0">Step 4</p>
                <p class="status mb-0">{{ $isCancelled ? 'Booking closed' : ($isCompleted ? 'Stay completed' : 'Upcoming stay') }}</p>
            </article>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="soft-card p-4 p-lg-5">
                <h2 class="h5 mb-3">Stay Information</h2>
                <div class="row g-3 booking-detail-grid">
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Room</small>
                        <strong>{{ $booking->room->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Room type</small>
                        <strong>{{ $booking->room->type ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Room view</small>
                        <strong>{{ $booking->room->view_type ?? 'Not specified' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Check-in</small>
                        <strong>{{ $booking->check_in->format('M d, Y') }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Check-out</small>
                        <strong>{{ $booking->check_out->format('M d, Y') }}</strong>
                    </div>
                    @if($booking->actual_check_in_at)
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Actual arrival</small>
                            <strong>{{ $booking->actual_check_in_at->format('M d, Y h:i A') }}</strong>
                        </div>
                    @endif
                    @if($booking->actual_check_out_at)
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Actual departure</small>
                            <strong>{{ $booking->actual_check_out_at->format('M d, Y h:i A') }}</strong>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Guests</small>
                        <strong>{{ $booking->guests }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Extra bedding</small>
                        <strong>{{ $booking->extra_bedding_count }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Nights</small>
                        <strong>{{ $billedUnits }}</strong>
                    </div>
                    @if(!empty($reservationMeta['adults']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Adults</small>
                            <strong>{{ $reservationMeta['adults'] }}</strong>
                        </div>
                    @endif
                    @if(isset($reservationMeta['kids']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Kids</small>
                            <strong>{{ $reservationMeta['kids'] }}</strong>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <small class="text-secondary d-block">Meal option</small>
                        <strong>{{ ($reservationMeta['meal_plan'] ?? 'room_only') === 'breakfast_included' ? 'Breakfast Included' : 'Room Only — No Breakfast' }}</strong>
                    </div>
                    @if(!empty($reservationMeta['payment_preference']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Payment preference</small>
                            <strong>{{ \App\Models\Payment::methodLabel((string) $reservationMeta['payment_preference']) }}</strong>
                        </div>
                    @endif
                    @if(!empty($reservationMeta['discount_type']) && $reservationMeta['discount_type'] !== 'none')
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Discount</small>
                            <strong>{{ strtoupper((string) $reservationMeta['discount_type']) }} (20%)</strong>
                        </div>
                    @endif
                    @if(!empty($reservationMeta['discount_id']))
                        <div class="col-md-6">
                            <small class="text-secondary d-block">Discount ID</small>
                            <strong>{{ $reservationMeta['discount_id'] }}</strong>
                        </div>
                    @endif
                    @if($discountProofUrl !== '')
                        <div class="col-12">
                            <small class="text-secondary d-block">Discount ID photo</small>
                            <a href="{{ $discountProofUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-ta-outline">View uploaded ID photo</a>
                        </div>
                    @endif
                </div>

                @if(!empty($reservationMeta))
                    <hr>
                    <h3 class="h6 mb-3">Guest Contact Details</h3>
                    <div class="row g-3">
                        @if(!empty($reservationMeta['first_name']) || !empty($reservationMeta['last_name']))
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Guest name</small>
                                <strong>{{ trim(($reservationMeta['first_name'] ?? '').' '.($reservationMeta['last_name'] ?? '')) }}</strong>
                            </div>
                        @endif
                        @if(!empty($reservationMeta['contact_email']))
                            <div class="col-md-6">
                                <small class="text-secondary d-block">E-mail</small>
                                <strong>{{ $reservationMeta['contact_email'] }}</strong>
                            </div>
                        @endif
                        @if(!empty($reservationMeta['contact_phone']))
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Phone</small>
                                <strong>{{ $reservationMeta['contact_phone'] }}</strong>
                            </div>
                        @endif
                        @php
                            $guestAddress = collect([
                                $reservationMeta['street_address'] ?? null,
                                $reservationMeta['street_address_line_2'] ?? null,
                                $reservationMeta['guest_city'] ?? null,
                                $reservationMeta['state_province'] ?? null,
                                $reservationMeta['postal_code'] ?? null,
                            ])->filter()->implode(', ');
                        @endphp
                        @if($guestAddress !== '')
                            <div class="col-md-6">
                                <small class="text-secondary d-block">Address</small>
                                <strong>{{ $guestAddress }}</strong>
                            </div>
                        @endif
                    </div>
                @endif

                @if($booking->notes)
                    <hr>
                    <small class="text-secondary d-block">Special request</small>
                    <p class="mb-0">{{ $booking->notes }}</p>
                @endif

                @if($canRequestReschedule || $hasPendingRescheduleRequest)
                    <hr>
                    <h3 class="h6 mb-3">Request Schedule Change</h3>

                    @if($hasPendingRescheduleRequest)
                        <div class="alert alert-info small">
                            Pending staff review:
                            <strong>{{ $booking->requested_check_in?->format('M d, Y') }}</strong>
                            to
                            <strong>{{ $booking->requested_check_out?->format('M d, Y') }}</strong>.
                            @if($booking->reschedule_requested_at)
                                Submitted {{ $booking->reschedule_requested_at->format('M d, Y h:i A') }}.
                            @endif
                        </div>
                        @if(filled($booking->reschedule_request_notes))
                            <p class="small text-secondary mb-3">Request note: <strong>{{ $booking->reschedule_request_notes }}</strong></p>
                        @endif
                    @endif

                    @if($canRequestReschedule)
                        <form method="POST" action="{{ route('bookings.request-reschedule', $booking) }}" class="row g-3">
                            @csrf
                            @method('PATCH')
                            <div class="col-md-6">
                                <label class="form-label">Requested check-in</label>
                                <input
                                    type="date"
                                    name="requested_check_in"
                                    class="form-control @error('requested_check_in') is-invalid @enderror"
                                    min="{{ now()->toDateString() }}"
                                    value="{{ old('requested_check_in', optional($booking->requested_check_in)->toDateString()) }}"
                                    required
                                >
                                @error('requested_check_in')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Requested check-out</label>
                                <input
                                    type="date"
                                    name="requested_check_out"
                                    class="form-control @error('requested_check_out') is-invalid @enderror"
                                    min="{{ now()->addDay()->toDateString() }}"
                                    value="{{ old('requested_check_out', optional($booking->requested_check_out)->toDateString()) }}"
                                    required
                                >
                                @error('requested_check_out')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason for change</label>
                                <textarea
                                    name="reschedule_request_notes"
                                    class="form-control @error('reschedule_request_notes') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Explain why you need to move the booking dates."
                                >{{ old('reschedule_request_notes', $booking->reschedule_request_notes) }}</textarea>
                                @error('reschedule_request_notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-ta">Send reschedule request</button>
                            </div>
                        </form>
                        <p class="small text-secondary mt-2 mb-0">This is available only for confirmed unpaid bookings before check-in.</p>
                    @else
                        <p class="small text-secondary mb-0">Schedule change requests are available only for confirmed unpaid bookings before check-in.</p>
                    @endif
                @endif

                @if($canRequestRoomTransfer || $hasPendingRoomTransferRequest)
                    <hr>
                    <h3 class="h6 mb-3">Request Room Transfer</h3>

                    @if($hasPendingRoomTransferRequest)
                        <div class="alert alert-info small">
                            Your room transfer request is pending staff review.
                            @if($booking->room_transfer_requested_at)
                                Submitted {{ $booking->room_transfer_requested_at->format('M d, Y h:i A') }}.
                            @endif
                        </div>
                        <p class="small text-secondary mb-3">
                            Submitted reason:
                            <strong>{{ $booking->room_transfer_request_reason }}</strong>
                        </p>
                    @endif

                    @if($canRequestRoomTransfer)
                        <form method="POST" action="{{ route('bookings.request-room-transfer', $booking) }}" class="row g-3">
                            @csrf
                            @method('PATCH')
                            <div class="col-12">
                                <label class="form-label">Reason for room transfer</label>
                                <textarea
                                    name="room_transfer_request_reason"
                                    class="form-control @error('room_transfer_request_reason') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Tell us why you need to move to a different room."
                                    required
                                >{{ old('room_transfer_request_reason', $booking->room_transfer_request_reason) }}</textarea>
                                @error('room_transfer_request_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-ta-outline">Send room transfer request</button>
                            </div>
                        </form>
                        <p class="small text-secondary mt-2 mb-0">Staff will review room availability and your request reason before approving the transfer.</p>
                    @else
                        <p class="small text-secondary mb-0">Room transfer requests are available only for active bookings before final check-out.</p>
                    @endif
                @endif

                @if($canRequestPaidRefund || $latestRefundRequest)
                    <hr>
                    <div id="refund-request"></div>
                    <h3 class="h6 mb-3">Refund Request</h3>

                    @if($latestRefundRequest)
                        <div class="alert alert-light border small">
                            <p class="mb-1">Status: <strong>{{ $refundStatusLabel }}</strong></p>
                            <p class="mb-1">Submitted: <strong>{{ optional($latestRefundRequest->requested_at)->format('M d, Y h:i A') ?? '-' }}</strong></p>
                            <p class="mb-0">Reason: <strong>{{ $latestRefundRequest->reason ?: 'No reason submitted yet.' }}</strong></p>
                            @if($latestRefundRequest->amount)
                                <p class="mb-0">Refund amount: <strong>₱{{ number_format((float) $latestRefundRequest->amount, 2) }}</strong></p>
                            @endif
                            @if($latestRefundRequest->refund_method)
                                <p class="mb-0">Refund method: <strong>{{ \App\Models\Payment::methodLabel($latestRefundRequest->refund_method) }}</strong></p>
                            @endif
                            @if($latestRefundRequest->transaction_reference)
                                <p class="mb-0">Reference: <strong>{{ $latestRefundRequest->transaction_reference }}</strong></p>
                            @endif
                            @if($latestRefundRequest->rejection_reason)
                                <p class="mb-0 text-danger">Decision: <strong>{{ $latestRefundRequest->rejection_reason }}</strong></p>
                            @endif
                        </div>
                    @endif

                    @if($canRequestPaidRefund)
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="row g-3">
                            @csrf
                            @method('PATCH')
                            <div class="col-12">
                                <label class="form-label">Reason for cancellation and refund</label>
                                <textarea
                                    name="cancellation_reason"
                                    class="form-control @error('cancellation_reason') is-invalid @enderror"
                                    rows="3"
                                    placeholder="Tell us why you are cancelling this paid booking."
                                    required
                                >{{ old('cancellation_reason', $latestRefundRequest?->reason) }}</textarea>
                                @error('cancellation_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Type CANCEL to confirm</label>
                                <input name="cancellation_confirmation" class="form-control @error('cancellation_confirmation') is-invalid @enderror" autocomplete="off" required>
                                @error('cancellation_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <button
                                    type="submit"
                                    class="btn btn-outline-danger"
                                    onclick="return confirm('Cancel this booking and submit a refund request?')"
                                >
                                    Cancel booking and request refund
                                </button>
                            </div>
                        </form>
                        <p class="small text-secondary mt-2 mb-0">
                            Refunds are returned through the original payment method{{ $refundMethodLabel ? ': '.$refundMethodLabel : '' }}.
                        </p>
                    @elseif($isRefundPending)
                        <p class="small text-secondary mb-0">Staff is currently reviewing this refund request.</p>
                    @endif
                @endif
            </section>
        </div>

        <div class="col-lg-4">
            <section class="soft-card p-4 booking-side-panel">
                <h2 class="h5 mb-3">Payment</h2>
                @if($booking->payment)
                    <p class="small text-secondary mb-1">Method: <strong>{{ \App\Models\Payment::methodLabel($booking->payment->method) }}</strong></p>
                    <p class="small text-secondary mb-1">Status: <strong>{{ ucfirst(str_replace('_', ' ', $booking->payment->status)) }}</strong></p>
                    <p class="small text-secondary mb-1">Recorded amount: <strong>&#8369;{{ number_format((float) $booking->payment->amount, 2) }}</strong></p>
                    @if($booking->payment->paid_at)
                        <p class="small text-secondary mb-1">Paid at: <strong>{{ $booking->payment->paid_at->format('M d, Y h:i A') }}</strong></p>
                    @endif
                    @if($booking->payment->verified_at)
                        <p class="small text-secondary mb-3">Verified by: <strong>{{ $booking->payment->source === 'paymongo_checkout' ? 'PayMongo (automatic)' : 'Hotel staff' }}</strong></p>
                    @endif
                @endif
                @if($isOnlineAwaitingVerification)
                    <p class="small text-secondary mb-1">Method selected: {{ \App\Models\Payment::methodLabel((string) ($booking->payment?->method ?? 'online')) }} (awaiting staff verification)</p>
                @endif
                @if($isCashAwaitingVerification)
                    <p class="small text-secondary mb-1">Method selected: Cash (waiting for staff confirmation)</p>
                @endif
                @if(filled($booking->payment?->customer_reference))
                    <p class="small text-secondary mb-1">Submitted Ref No: <strong>{{ $booking->payment->customer_reference }}</strong></p>
                @endif
                @if($paymentProofUrl !== '')
                    <p class="small text-secondary mb-1">Submitted Proof: <a href="{{ $paymentProofUrl }}" target="_blank" rel="noopener">View uploaded screenshot</a></p>
                @endif
                @if($isRefundPending && $refundMethodLabel)
                    <p class="small text-secondary mb-1">Refund method: <strong>{{ $refundMethodLabel }}</strong></p>
                @endif
                <p class="mb-1"><small class="text-secondary">Room subtotal</small><br><strong>&#8369;{{ number_format((float) ($pricingQuote['room_total'] ?? $booking->total_price), 2) }}</strong></p>
                @if(($pricingQuote['extra_bedding_total'] ?? 0) > 0)
                    <p class="mb-1"><small class="text-secondary">Extra bedding total</small><br><strong>&#8369;{{ number_format((float) $pricingQuote['extra_bedding_total'], 2) }}</strong></p>
                @endif
                <p class="mb-3"><small class="text-secondary">Total amount</small><br><strong>&#8369;{{ number_format($booking->total_price, 2) }}</strong></p>

                <div class="booking-next-card mb-3">
                    <small class="text-secondary d-block mb-1">Next action</small>
                    <strong class="small d-block">{{ $nextAction }}</strong>
                </div>

                @if($booking->payment?->transaction_reference)
                    <p class="small text-secondary mb-3">Transaction Reference: <strong>{{ $booking->payment->transaction_reference }}</strong></p>
                @endif
                @if($booking->payment?->provider_payment_id)
                    <p class="small text-secondary mb-3">PayMongo Payment ID: <strong>{{ $booking->payment->provider_payment_id }}</strong></p>
                @endif

                <div class="d-grid gap-2">
                    @if(!$isCashAwaitingVerification && !$isOnlineAwaitingVerification && $booking->payment_status !== 'paid' && $booking->status === 'confirmed')
                        <a href="{{ route('payments.checkout', $booking) }}" class="btn btn-ta">Complete payment</a>
                    @endif

                    @if($booking->payment_status === 'paid')
                        <a href="{{ route('bookings.receipt', $booking) }}" class="btn btn-ta-outline">Download receipt (PDF)</a>
                    @endif

                    @if($canRequestPaidRefund)
                        <a href="#refund-request" class="btn btn-outline-danger">Go to refund request</a>
                    @endif

                    @if($canCancelWithoutRefund)
                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="d-grid gap-2">
                            @csrf
                            @method('PATCH')
                            <textarea name="cancellation_reason" class="form-control form-control-sm" rows="2" placeholder="Reason for cancellation" required>{{ old('cancellation_reason') }}</textarea>
                            <input name="cancellation_confirmation" class="form-control form-control-sm" placeholder="Type CANCEL" autocomplete="off" required>
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this booking?')">Cancel booking</button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
