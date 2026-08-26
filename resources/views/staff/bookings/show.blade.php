@extends('layouts.staff')

@section('title', 'Booking Details')

@push('head')
    <style>
        .booking-shell,
        .booking-side-shell {
            border-radius: 14px;
            border: 1px solid #d9e1ef;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 0.75rem;
        }
        .booking-info-item {
            border: 1px solid #e2e8f3;
            border-radius: 12px;
            background: #fafcff;
            padding: 0.64rem 0.7rem;
        }
        .booking-info-label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.22rem;
        }
        .booking-info-value {
            font-size: 0.92rem;
            color: #1f2937;
            font-weight: 700;
            word-break: break-word;
        }
        .booking-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .booking-actions form {
            margin: 0;
        }
        .booking-actions .btn {
            min-height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.32rem;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1;
            padding: 0.45rem 0.78rem;
        }
        .booking-actions .btn-staff {
            box-shadow: 0 5px 10px rgba(var(--theme-primary-rgb), 0.18);
        }
        .booking-actions .btn-staff:hover {
            box-shadow: 0 8px 14px rgba(var(--theme-secondary-rgb), 0.2);
        }
        .btn-staff-danger {
            border-radius: 10px;
            border: 1px solid rgba(var(--theme-secondary-rgb), 0.52);
            color: var(--theme-secondary);
            background: rgba(var(--theme-secondary-rgb), 0.08);
        }
        .btn-staff-danger:hover,
        .btn-staff-danger:focus {
            border-color: var(--theme-secondary);
            background: var(--theme-secondary);
            color: #fff;
        }
        .booking-note {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.35rem;
        }
        .booking-side-shell {
            position: sticky;
            top: 84px;
        }
        .booking-meta-line {
            display: flex;
            justify-content: space-between;
            gap: 0.65rem;
            padding: 0.45rem 0;
            border-bottom: 1px dashed #dce4f2;
            font-size: 0.9rem;
        }
        .booking-meta-line:last-child {
            border-bottom: 0;
        }
        .booking-meta-label {
            color: #64748b;
            font-weight: 700;
        }
        .booking-meta-value {
            color: #1f2937;
            font-weight: 800;
            text-align: right;
        }
        @media (max-width: 1199.98px) {
            .booking-side-shell {
                position: static;
            }
        }
        .booking-top-chip {
            border-radius: 999px;
            border: 1px solid #d7deec;
            background: #f8fbff;
            color: #334155;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.22rem 0.58rem;
            display: inline-flex;
            align-items: center;
        }
        .booking-top-chip.success {
            border-color: rgba(6, 118, 71, 0.3);
            background: rgba(6, 118, 71, 0.1);
            color: #067647;
        }
        .booking-top-chip.warning {
            border-color: rgba(154, 103, 0, 0.32);
            background: rgba(245, 158, 11, 0.12);
            color: #805500;
        }
        .booking-top-chip.danger {
            border-color: rgba(180, 35, 24, 0.3);
            background: rgba(180, 35, 24, 0.09);
            color: #b42318;
        }
        .booking-log-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 0.9rem;
        }
        .booking-log-card {
            border: 1px solid #dce5f3;
            border-radius: 14px;
            background: #fbfdff;
            padding: 0.95rem;
        }
        .booking-log-time {
            font-size: 1rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.65rem;
        }
        .booking-command-bar {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            border: 1px solid #d9e1ef;
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .booking-command-item { padding: .9rem 1rem; border-right: 1px solid #e2e8f3; }
        .booking-command-item:last-child { border-right: 0; }
        .booking-command-value { color: #172033; font-size: .95rem; font-weight: 800; line-height: 1.35; }
        .booking-next-step {
            display: flex;
            align-items: center;
            gap: .8rem;
            border-left: 4px solid var(--staff-brand);
            background: rgba(var(--theme-primary-rgb), .1);
            padding: .8rem 1rem;
        }
        .booking-next-step i { color: var(--theme-primary); font-size: 1.15rem; }
        .booking-section-nav { display: flex; flex-wrap: wrap; gap: .4rem; }
        .booking-section-nav a {
            border: 1px solid #d7deec;
            border-radius: 6px;
            background: #fff;
            color: #334155;
            padding: .4rem .65rem;
            font-size: .78rem;
            font-weight: 700;
            text-decoration: none;
        }
        .booking-section-nav a:hover,
        .booking-section-nav a:focus { border-color: var(--staff-brand); color: var(--theme-primary); }
        @media (max-width: 767.98px) {
            .booking-command-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .booking-command-item:nth-child(2) { border-right: 0; }
            .booking-command-item:nth-child(-n+2) { border-bottom: 1px solid #e2e8f3; }
        }
    </style>
@endpush

@section('content')
    @php
        $reservationMeta = $booking->reservation_meta ?? [];
        $displayName = $booking->guestName();
        $displayEmail = $booking->guestEmail();
        $displayPhone = $booking->guestPhone();
        $discountProofPath = (string) data_get($reservationMeta, 'discount_id_photo_path', '');
        $discountProofUrl = $discountProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($discountProofPath)
            : '';
        $paymentProofPath = trim((string) ($booking->payment?->payment_proof_path ?? ''));
        $paymentProofUrl = $paymentProofPath !== ''
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($paymentProofPath)
            : '';
        $latestRefundRequest = $booking->latestRefundRequest;
        $refundRequestStatusLabel = $latestRefundRequest
            ? ucfirst(str_replace('_', ' ', (string) $latestRefundRequest->status))
            : null;
        $refundMethodLabel = $booking->payment
            ? \App\Models\Payment::methodLabel((string) $booking->payment->method)
            : null;
        $profileAddress = trim(collect([
            $booking->user?->address_line,
            $booking->user?->city,
            $booking->user?->country,
        ])->filter()->implode(', '));
        $guestAddress = collect([
            $reservationMeta['street_address'] ?? null,
            $reservationMeta['street_address_line_2'] ?? null,
            $reservationMeta['guest_city'] ?? null,
            $reservationMeta['state_province'] ?? null,
            $reservationMeta['postal_code'] ?? null,
        ])->filter()->implode(', ');
        $bookingStatusLabel = \App\Models\Booking::statusLabel($booking->status);
        $paymentStatusLabel = ucfirst(str_replace('_', ' ', $booking->payment_status));
        $billedUnits = $booking->nights();
        $isOnlineAwaitingVerification = $booking->payment_status === 'pending_verification'
            && \App\Models\Payment::isOnlineMethod((string) ($booking->payment?->method ?? ''));
        $hasPendingRescheduleRequest = $booking->hasPendingRescheduleRequest();
        $hasPendingRoomTransferRequest = $booking->hasPendingRoomTransferRequest();
        $canStaffDirectlyReschedule = $booking->canBeRescheduledByStaff();
        $canStaffTransferRoom = $booking->canBeTransferredByStaff();
        $defaultCheckInTime = old('actual_check_in_at', now()->format('Y-m-d\TH:i'));
        $defaultCheckOutTime = old('actual_check_out_at', now()->format('Y-m-d\TH:i'));
        $standardGuests = \App\Models\Room::standardGuestCapacity();
        $currentAdults = max(1, (int) old('adults', $booking->guestDetail?->adults ?? max(1, $booking->guests)));
        $currentKids = max(0, (int) old('kids', $booking->guestDetail?->kids ?? 0));
        $currentOccupancyTotal = $currentAdults + $currentKids;
        $currentExtraBedding = max(0, $currentOccupancyTotal - $standardGuests);
        $pricingQuote = $booking->pricingQuote();
        $isAssignedStaff = (int) $booking->staff_id === (int) auth('staff')->id();
        $bookingChipClass = match ($booking->status) {
            'confirmed', 'completed' => 'success',
            'cancelled' => 'danger',
            default => 'warning',
        };
        $paymentChipClass = match ($booking->payment_status) {
            'paid' => 'success',
            'refund_pending' => 'warning',
            default => $booking->status === 'cancelled' ? 'danger' : 'warning',
        };
        $nextStep = match (true) {
            $booking->status === 'cancelled' => 'No arrival action is required. Review refund information if a payment was collected.',
            $booking->status === 'completed' => 'Stay completed. Confirm the receipt and internal notes are complete.',
            $isOnlineAwaitingVerification => 'Verify the submitted online payment proof before continuing.',
            $booking->status === 'pending' => 'Review the reservation details and confirm the booking.',
            is_null($booking->actual_check_in_at) => 'Prepare for arrival and record check-in when the guest reaches the hotel.',
            is_null($booking->actual_check_out_at) && $booking->payment_status !== 'paid' => 'Collect and record payment before guest check-out.',
            is_null($booking->actual_check_out_at) => 'Record the guest departure to complete this stay.',
            default => 'Review the booking record and complete any remaining staff notes.',
        };
    @endphp

    <section class="mb-4">
        <div class="mb-2">
            <x-back-button :href="$backUrl" label="Back to bookings" class="btn-sm" />
        </div>
        <h1 class="h4 mb-1">Booking #{{ $booking->id }}</h1>
        <div class="d-flex flex-wrap gap-2">
            <span class="booking-top-chip {{ $bookingChipClass }}">Booking: {{ $bookingStatusLabel }}</span>
            <span class="booking-top-chip {{ $paymentChipClass }}">Payment: {{ $paymentStatusLabel }}</span>
            <span class="booking-top-chip">Guest: {{ $displayName }}</span>
        </div>
        @if($booking->status === 'confirmed' && $booking->payment_status === 'unpaid' && $booking->payment_due_at)
            <p class="small text-danger fw-semibold mt-2 mb-0">Payment due {{ $booking->payment_due_at->format('M d, Y h:i A') }} — auto-cancels when overdue.</p>
        @endif
    </section>

    <section class="mb-3" aria-label="Booking operational summary">
        <div class="booking-command-bar mb-3">
            <div class="booking-command-item">
                <p class="booking-info-label">Stay</p>
                <div class="booking-command-value">{{ $booking->check_in->format('M d') }} - {{ $booking->check_out->format('M d, Y') }}</div>
            </div>
            <div class="booking-command-item">
                <p class="booking-info-label">Room</p>
                <div class="booking-command-value">{{ $booking->room->name ?? 'Not assigned' }}</div>
            </div>
            <div class="booking-command-item">
                <p class="booking-info-label">Occupancy</p>
                <div class="booking-command-value">{{ $booking->guests }} guest{{ $booking->guests === 1 ? '' : 's' }} &middot; {{ $billedUnits }} night{{ $billedUnits === 1 ? '' : 's' }}</div>
            </div>
            <div class="booking-command-item">
                <p class="booking-info-label">Accommodation</p>
                <div class="booking-command-value">PHP {{ number_format((float) ($pricingQuote['chargeable_subtotal'] ?? 0), 2) }}</div>
            </div>
            <div class="booking-command-item">
                <p class="booking-info-label">Service / Local / VAT</p>
                <div class="booking-command-value">PHP {{ number_format((float) ($pricingQuote['service_fee'] ?? 0), 2) }} / PHP {{ number_format((float) ($pricingQuote['local_tax'] ?? 0), 2) }} / PHP {{ number_format((float) ($pricingQuote['vat'] ?? 0), 2) }}</div>
            </div>
            <div class="booking-command-item">
                <p class="booking-info-label">Amount Due</p>
                <div class="booking-command-value">PHP {{ number_format((float) ($booking->payment?->amount ?? $booking->total_price), 2) }}</div>
            </div>
        </div>
        <div class="booking-next-step mb-3">
            <i class="bi bi-compass" aria-hidden="true"></i>
            <div><span class="booking-info-label d-block mb-1">Recommended Next Step</span><strong>{{ $nextStep }}</strong></div>
        </div>
        <nav class="booking-section-nav" aria-label="Booking detail sections">
            <a href="#arrival-departure-log"><i class="bi bi-door-open me-1" aria-hidden="true"></i>Arrival</a>
            <a href="#guest-stay-information"><i class="bi bi-person-vcard me-1" aria-hidden="true"></i>Guest &amp; stay</a>
            <a href="#occupancy-update"><i class="bi bi-people me-1" aria-hidden="true"></i>Occupancy</a>
            <a href="#payment-desk"><i class="bi bi-credit-card me-1" aria-hidden="true"></i>Payment</a>
            <a href="#internal-staff-notes"><i class="bi bi-journal-text me-1" aria-hidden="true"></i>Staff notes</a>
        </nav>
    </section>

    <div class="booking-actions mb-3" id="booking-top-actions">
        @if($booking->canBeConfirmedByStaff())
            <form method="POST" action="{{ route('staff.bookings.confirm', $booking) }}" data-confirm="Confirm this booking now?">
                @csrf
                @method('PATCH')
                @if(!empty($returnTo))
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <input type="hidden" name="stay_on_booking" value="1">
                <input type="hidden" name="redirect_section" value="booking-top-actions">
                <button type="submit" class="btn btn-staff">
                    <i class="bi bi-check2-circle"></i>
                    <span>Confirm booking</span>
                </button>
            </form>
        @endif

        @if(in_array($booking->status, ['pending', 'confirmed'], true) && is_null($booking->actual_check_in_at))
            <form method="POST" action="{{ route('staff.bookings.cancel', $booking) }}" data-confirm="Cancel this booking?">
                @csrf
                @method('PATCH')
                @if(!empty($returnTo))
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <input type="hidden" name="stay_on_booking" value="1">
                <input type="hidden" name="redirect_section" value="booking-top-actions">
                <button type="submit" class="btn btn-staff-danger">
                    <i class="bi bi-x-circle"></i>
                    <span>Cancel booking</span>
                </button>
            </form>
        @endif

        @if($booking->payment_status === 'paid')
            <a href="{{ route('staff.bookings.receipt', $booking) }}" class="btn btn-staff-outline">
                <i class="bi bi-file-earmark-arrow-down"></i>
                <span>Receipt PDF</span>
            </a>
        @endif
    </div>

    <section class="booking-shell p-3 p-lg-4 mb-4" id="arrival-departure-log">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
            <div>
                <h2 class="h5 mb-1">Arrival and departure</h2>
                <p class="booking-note mb-0">Record the guest’s actual check-in and check-out times.</p>
            </div>
        </div>
        <div class="booking-log-grid">
            <article class="booking-log-card">
                <p class="booking-info-label">Actual Check-In</p>
                <p class="booking-log-time">{{ optional($booking->actual_check_in_at)->format('M d, Y h:i A') ?? 'Not logged yet' }}</p>

                @if($booking->canBeCheckedInByStaff() && $isAssignedStaff)
                    <form method="POST" action="{{ route('staff.bookings.check-in', $booking) }}" class="row g-3 align-items-end" data-confirm="Save this guest check-in time?">
                        @csrf
                        @method('PATCH')
                        @if(!empty($returnTo))
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif
                        <input type="hidden" name="stay_on_booking" value="1">
                        <input type="hidden" name="redirect_section" value="arrival-departure-log">
                        <div class="col-12">
                            <label class="form-label">Arrival date and time</label>
                            <input
                                type="datetime-local"
                                name="actual_check_in_at"
                                class="form-control @error('actual_check_in_at') is-invalid @enderror"
                                value="{{ $defaultCheckInTime }}"
                                max="{{ now()->format('Y-m-d\TH:i') }}"
                                required
                            >
                            @error('actual_check_in_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-staff w-100">
                                <i class="bi bi-door-open"></i>
                                <span>Save check-in</span>
                            </button>
                        </div>
                    </form>
                @elseif(!$booking->staff_id)
                    <div class="alert alert-warning small mb-0">Admin assignment is required before check-in.</div>
                @elseif(!$isAssignedStaff)
                    <div class="alert alert-info small mb-0">Only {{ $booking->assignedStaff?->name ?? 'the assigned staff member' }} can record this check-in.</div>
                @elseif($booking->payment_status !== 'paid')
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-credit-card me-1" aria-hidden="true"></i>
                        Record the full payment before checking in this guest.
                        <a href="#payment-desk" class="alert-link">Go to Payment Desk</a>
                    </div>
                @elseif($booking->actual_check_in_at)
                    <p class="booking-note mb-0">This is the staff-recorded arrival time for the guest.</p>
                @else
                    <p class="booking-note mb-0">Check-in becomes available after the booking is confirmed and the guest arrival date has started.</p>
                @endif
            </article>

            <article class="booking-log-card">
                <p class="booking-info-label">Actual Check-Out</p>
                <p class="booking-log-time">{{ optional($booking->actual_check_out_at)->format('M d, Y h:i A') ?? 'Not logged yet' }}</p>

                @if($booking->canBeCheckedOutByStaff() && $booking->payment_status === 'paid' && $isAssignedStaff)
                    <form method="POST" action="{{ route('staff.bookings.check-out', $booking) }}" class="row g-3 align-items-end" data-confirm="Save this guest check-out time and complete the booking?">
                        @csrf
                        @method('PATCH')
                        @if(!empty($returnTo))
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif
                        <input type="hidden" name="stay_on_booking" value="1">
                        <input type="hidden" name="redirect_section" value="arrival-departure-log">
                        <div class="col-12">
                            <label class="form-label">Departure date and time</label>
                            <input
                                type="datetime-local"
                                name="actual_check_out_at"
                                class="form-control @error('actual_check_out_at') is-invalid @enderror"
                                value="{{ $defaultCheckOutTime }}"
                                max="{{ now()->format('Y-m-d\TH:i') }}"
                                required
                            >
                            @error('actual_check_out_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-staff w-100">
                                <i class="bi bi-door-closed"></i>
                                <span>Save check-out</span>
                            </button>
                        </div>
                    </form>
                @elseif($booking->actual_check_out_at)
                    <p class="booking-note mb-0">This is the staff-recorded departure time for the guest.</p>
                @elseif($booking->canBeCheckedOutByStaff() && !$isAssignedStaff)
                    <p class="booking-note mb-0">Only the assigned staff member can record this check-out.</p>
                @elseif($booking->canBeCheckedOutByStaff())
                    <p class="booking-note mb-0">Mark the booking payment as paid first before recording the guest check-out.</p>
                @else
                    <p class="booking-note mb-0">Check-out becomes available after the guest has been checked in.</p>
                @endif
            </article>
        </div>
    </section>

    @if($booking->status === 'completed' && $isAssignedStaff && $booking->room?->roomStatus?->slug === 'dirty')
        <section class="booking-shell p-3 p-lg-4 mb-4">
            <h2 class="h5 mb-2">Room cleaning</h2>
            <p class="booking-note">After housekeeping finishes, mark the room clean so it can be offered to the next guest.</p>
            <form method="POST" action="{{ route('staff.bookings.room-clean', $booking) }}" data-confirm="Confirm that this room has been cleaned and inspected?">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-staff"><i class="bi bi-check-circle"></i> Mark room clean</button>
            </form>
        </section>
    @endif

    <div id="schedule-management"></div>

    @if($hasPendingRescheduleRequest)
        <section class="booking-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Pending Schedule Change Request</h2>
                    <p class="booking-note mb-0">Customer requested a new stay schedule. Apply it only if the requested dates are still available.</p>
                </div>
            </div>
            <div class="booking-info-grid mb-3">
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Dates</p>
                    <p class="booking-info-value">{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Requested Dates</p>
                    <p class="booking-info-value">{{ $booking->requested_check_in?->format('M d, Y') ?? '-' }} - {{ $booking->requested_check_out?->format('M d, Y') ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Requested At</p>
                    <p class="booking-info-value">{{ optional($booking->reschedule_requested_at)->format('M d, Y h:i A') ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Customer Note</p>
                    <p class="booking-info-value">{{ $booking->reschedule_request_notes ?: '-' }}</p>
                </div>
            </div>
            <div class="booking-actions">
                <form method="POST" action="{{ route('staff.bookings.apply-reschedule-request', $booking) }}" data-confirm="Apply this requested schedule to the booking now?">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <input type="hidden" name="stay_on_booking" value="1">
                    <input type="hidden" name="redirect_section" value="schedule-management">
                    <button type="submit" class="btn btn-staff">
                        <i class="bi bi-calendar-check"></i>
                        <span>Apply requested schedule</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('staff.bookings.decline-reschedule-request', $booking) }}" data-confirm="Decline and clear this schedule change request?">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <input type="hidden" name="stay_on_booking" value="1">
                    <input type="hidden" name="redirect_section" value="schedule-management">
                    <button type="submit" class="btn btn-staff-outline">
                        <i class="bi bi-calendar-x"></i>
                        <span>Decline request</span>
                    </button>
                </form>
            </div>
        </section>
    @endif

    <div id="room-transfer-management"></div>

    @if($hasPendingRoomTransferRequest)
        <section class="booking-shell p-3 p-lg-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Pending Room Transfer Request</h2>
                    <p class="booking-note mb-0">Customer requested a room change. Review the reason, then use the room transfer section below if you approve.</p>
                </div>
            </div>
            <div class="booking-info-grid mb-3">
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Room</p>
                    <p class="booking-info-value">Room {{ $booking->room->name ?? $booking->room_id }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Requested At</p>
                    <p class="booking-info-value">{{ optional($booking->room_transfer_requested_at)->format('M d, Y h:i A') ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Customer Reason</p>
                    <p class="booking-info-value">{{ $booking->room_transfer_request_reason ?: '-' }}</p>
                </div>
            </div>
            <div class="booking-actions">
                <form method="POST" action="{{ route('staff.bookings.decline-room-transfer-request', $booking) }}" data-confirm="Decline and clear this room transfer request?">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <input type="hidden" name="stay_on_booking" value="1">
                    <input type="hidden" name="redirect_section" value="room-transfer-management">
                    <button type="submit" class="btn btn-staff-outline">
                        <i class="bi bi-x-circle"></i>
                        <span>Decline request</span>
                    </button>
                </form>
            </div>
        </section>
    @endif

    @if($canStaffDirectlyReschedule)
        <section class="booking-shell p-3 p-lg-4 mb-4" id="direct-staff-reschedule">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Direct Staff Reschedule</h2>
                    <p class="booking-note mb-0">Use this when the customer asks for a schedule change in person at the hotel. The system will recheck availability and update the due amount automatically when the booking is not yet paid.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('staff.bookings.reschedule', $booking) }}" class="row g-3" data-confirm="Update this booking schedule now?">
                @csrf
                @method('PATCH')
                @if(!empty($returnTo))
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                @endif
                <input type="hidden" name="stay_on_booking" value="1">
                <input type="hidden" name="redirect_section" value="direct-staff-reschedule">
                <div class="col-md-6">
                    <label class="form-label">New check-in</label>
                    <input
                        type="date"
                        name="check_in"
                        class="form-control @error('check_in') is-invalid @enderror"
                        min="{{ now()->toDateString() }}"
                        value="{{ old('check_in', $booking->check_in->toDateString()) }}"
                        required
                    >
                    @error('check_in')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">New check-out</label>
                    <input
                        type="date"
                        name="check_out"
                        class="form-control @error('check_out') is-invalid @enderror"
                        min="{{ now()->addDay()->toDateString() }}"
                        value="{{ old('check_out', $booking->check_out->toDateString()) }}"
                        required
                    >
                    @error('check_out')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-staff">
                        <i class="bi bi-calendar2-week"></i>
                        <span>Update schedule</span>
                    </button>
                </div>
            </form>
        </section>
    @endif

    @if($canStaffTransferRoom)
        <section class="booking-shell p-3 p-lg-4 mb-4" id="room-transfer">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                    <h2 class="h5 mb-1">Room Transfer</h2>
                    <p class="booking-note mb-0">
                        Use this when the guest asks staff to move them to another room while keeping the same stay dates.
                        @if($transferRequiresSameTotal)
                            Only same-total rooms are listed because this booking already has a submitted or recorded payment.
                        @else
                            If the booking is still unpaid, the amount due will update automatically after the transfer.
                        @endif
                    </p>
                </div>
            </div>

            <div class="booking-info-grid mb-3">
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Room</p>
                    <p class="booking-info-value">{{ $booking->room->name ?? '-' }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Guests in Booking</p>
                    <p class="booking-info-value">{{ $booking->guests }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Extra Bedding Needed</p>
                    <p class="booking-info-value">{{ $booking->extra_bedding_count }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Stay Dates</p>
                    <p class="booking-info-value">{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                </div>
                <div class="booking-info-item">
                    <p class="booking-info-label">Current Stay Total</p>
                    <p class="booking-info-value">PHP {{ number_format($currentStayTotal, 2) }}</p>
                </div>
            </div>

            @if($transferRooms->isNotEmpty())
                <form method="POST" action="{{ route('staff.bookings.transfer-room', $booking) }}" class="row g-3 align-items-end" data-confirm="Move this booking to the selected room now?">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <input type="hidden" name="stay_on_booking" value="1">
                    <input type="hidden" name="redirect_section" value="room-transfer-management">
                    <div class="col-lg-8">
                        <label class="form-label">New room</label>
                        <select name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                            <option value="">Select available room...</option>
                            @foreach($transferRooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>
                                    {{ $room->name }} ({{ $room->type ?? 'Room' }}{{ filled($room->view_type) ? ', '.$room->view_type : '' }} - standard {{ $standardGuests }} guests) - PHP {{ number_format((float) $room->transfer_stay_total, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('room_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-lg-4">
                        <button type="submit" class="btn btn-staff w-100">
                            <i class="bi bi-arrow-left-right"></i>
                            <span>Transfer room</span>
                        </button>
                    </div>
                </form>
            @else
                <p class="booking-note mb-0">
                    No matching rooms are currently available for this booking's stay dates.
                </p>
            @endif
        </section>
    @endif

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="booking-shell p-3 p-lg-4 mb-4" id="guest-stay-information">
                <h2 class="h5 mb-3">Guest & Stay Information</h2>
                <div class="booking-info-grid">
                    <div class="booking-info-item">
                        <p class="booking-info-label">Guest Name</p>
                        <p class="booking-info-value">{{ $displayName }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Email</p>
                        <p class="booking-info-value">{{ $displayEmail }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Phone</p>
                        <p class="booking-info-value">{{ $displayPhone }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Room</p>
                        <p class="booking-info-value">{{ $booking->room->name ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Stay Dates</p>
                        <p class="booking-info-value">{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Stay Type</p>
                        <p class="booking-info-value">Nightly</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Total Guests</p>
                        <p class="booking-info-value">{{ $booking->guests }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Standard Occupancy</p>
                        <p class="booking-info-value">{{ $standardGuests }} guests</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Extra Bedding Needed</p>
                        <p class="booking-info-value">{{ $booking->extra_bedding_count }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Nights</p>
                        <p class="booking-info-value">{{ $billedUnits }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Actual Check-In</p>
                        <p class="booking-info-value">{{ optional($booking->actual_check_in_at)->format('M d, Y h:i A') ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Actual Check-Out</p>
                        <p class="booking-info-value">{{ optional($booking->actual_check_out_at)->format('M d, Y h:i A') ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Assigned Staff</p>
                        <p class="booking-info-value">{{ $booking->assignedStaff->name ?? '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Profile Address</p>
                        <p class="booking-info-value">{{ $profileAddress ?: '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Reservation Address</p>
                        <p class="booking-info-value">{{ $guestAddress !== '' ? $guestAddress : '-' }}</p>
                    </div>
                    <div class="booking-info-item">
                        <p class="booking-info-label">Meal Option</p>
                        <p class="booking-info-value">{{ ($reservationMeta['meal_plan'] ?? 'room_only') === 'breakfast_included' ? 'Breakfast Included' : 'Room Only — No Breakfast' }}</p>
                    </div>
                    @if(!empty($reservationMeta['payment_preference']))
                        <div class="booking-info-item">
                            <p class="booking-info-label">Preferred Payment</p>
                            <p class="booking-info-value">{{ ucfirst(str_replace('_', ' ', $reservationMeta['payment_preference'])) }}</p>
                        </div>
                    @endif
                    @if(!empty($reservationMeta['discount_type']) && $reservationMeta['discount_type'] !== 'none')
                        <div class="booking-info-item">
                            <p class="booking-info-label">Requested Discount</p>
                            <p class="booking-info-value">{{ $reservationMeta['discount_type'] === 'promo' ? 'PROMO '.$reservationMeta['promo_code'].' ('.number_format((float) $reservationMeta['promo_discount_percent'], 2).'%)' : strtoupper((string) $reservationMeta['discount_type']).' (20%)' }}</p>
                        </div>
                    @endif
                </div>

                @if($booking->notes)
                    <p class="booking-note"><strong>Guest Notes:</strong> {{ $booking->notes }}</p>
                @endif
            </section>

            <section class="booking-shell p-3 p-lg-4 mb-4" id="occupancy-update">
                <h2 class="h5 mb-2">Occupancy Update</h2>
                <p class="booking-note mb-3">All rooms use a standard 2-guest setup. Guests beyond 2 require extra bedding approval from staff.</p>
                <form method="POST" action="{{ route('staff.bookings.occupancy', $booking) }}" class="row g-3">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <input type="hidden" name="stay_on_booking" value="1">
                    <input type="hidden" name="redirect_section" value="occupancy-update">
                    <div class="col-md-4">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" min="1" max="20" class="form-control @error('adults') is-invalid @enderror" value="{{ $currentAdults }}" required>
                        @error('adults')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Kids</label>
                        <input type="number" name="kids" min="0" max="20" class="form-control @error('kids') is-invalid @enderror" value="{{ $currentKids }}">
                        @error('kids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Current Total Guests</label>
                        <div class="static-field" aria-label="Calculated current total guests">{{ $currentOccupancyTotal }} guest{{ $currentOccupancyTotal === 1 ? '' : 's' }} &middot; Calculated automatically</div>
                    </div>
                    <div class="col-12 {{ $currentOccupancyTotal >= 3 ? '' : 'd-none' }}" id="occupancy_extra_bedding_group">
                        <div class="form-check">
                            <input
                                class="form-check-input @error('extra_bedding_confirmed') is-invalid @enderror"
                                type="checkbox"
                                value="1"
                                id="extra_bedding_confirmed"
                                name="extra_bedding_confirmed"
                                @checked(old('extra_bedding_confirmed', $booking->extra_bedding_count > 0))
                                @disabled($currentOccupancyTotal < 3)
                            >
                            <label class="form-check-label" for="extra_bedding_confirmed">
                                Confirm extra bedding for guest 3 and above.
                                @if($currentExtraBedding > 0)
                                    This update adds {{ $currentExtraBedding }} guest{{ $currentExtraBedding === 1 ? '' : 's' }} beyond the 2-guest standard occupancy.
                                @else
                                    This is only required when the total guest count goes above 2.
                                @endif
                            </label>
                            @error('extra_bedding_confirmed')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-staff w-100">Save occupancy</button>
                    </div>
                </form>
            </section>

            @if($latestRefundRequest)
                <section class="booking-shell p-3 p-lg-4 mb-4" id="refund-request">
                    <h2 class="h5 mb-2">Refund Request</h2>
                    <p class="booking-note mb-3">Review the submitted refund reason here before coordinating refund approval or payment return.</p>
                    <div class="booking-info-grid mb-3">
                        <div class="booking-info-item">
                            <p class="booking-info-label">Request Status</p>
                            <p class="booking-info-value">{{ $refundRequestStatusLabel }}</p>
                        </div>
                        <div class="booking-info-item">
                            <p class="booking-info-label">Requested At</p>
                            <p class="booking-info-value">{{ optional($latestRefundRequest->requested_at)->format('M d, Y h:i A') ?? '-' }}</p>
                        </div>
                        <div class="booking-info-item">
                            <p class="booking-info-label">Refund Method</p>
                            <p class="booking-info-value">{{ $refundMethodLabel ?? '-' }}</p>
                        </div>
                        <div class="booking-info-item">
                            <p class="booking-info-label">Refund Amount</p>
                            <p class="booking-info-value">PHP {{ number_format((float) ($booking->payment?->amount ?? 0), 2) }}</p>
                        </div>
                    </div>
                    <div class="booking-info-item mb-3">
                        <p class="booking-info-label">Refund Reason</p>
                        <p class="booking-info-value">{{ $latestRefundRequest->reason ?: 'No refund reason submitted.' }}</p>
                    </div>
                    @if(filled($latestRefundRequest->notes))
                        <p class="booking-note mb-0"><strong>System Note:</strong> {{ $latestRefundRequest->notes }}</p>
                    @endif
                </section>
            @endif

            <section class="booking-shell p-3 p-lg-4 mb-4" id="internal-staff-notes">
                <h2 class="h5 mb-3">Internal Staff Notes</h2>
                <form method="POST" action="{{ route('staff.bookings.staff-notes', $booking) }}" class="row g-3">
                    @csrf
                    @method('PATCH')
                    @if(!empty($returnTo))
                        <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    @endif
                    <input type="hidden" name="stay_on_booking" value="1">
                    <input type="hidden" name="redirect_section" value="internal-staff-notes">
                    <div class="col-12">
                        <label class="form-label">Visible to staff/admin only</label>
                        <textarea
                            name="staff_notes"
                            class="form-control"
                            rows="4"
                            placeholder="Add check-in reminders, payment follow-up, housekeeping coordination, or special handling notes."
                        >{{ old('staff_notes', $booking->staff_notes) }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-staff w-100">Save notes</button>
                    </div>
                </form>
            </section>

        </div>
        <div class="col-xl-4">
            <aside class="booking-side-shell p-3 p-lg-4" id="payment-desk">
                <h2 class="h5 mb-3">Payment Desk</h2>

                @if($isOnlineAwaitingVerification)
                    <div class="alert alert-info small mb-3">
                        <strong>Waiting for admin verification.</strong> The customer submitted an online payment. Staff cannot approve or reject online payments; continue only after an administrator verifies it.
                    </div>
                @endif

                @php
                    $staffCanRecordCash = $booking->payment_status === 'unpaid'
                        && $booking->status === 'confirmed'
                        && in_array(strtolower((string) ($booking->payment?->method ?? 'pending')), ['', 'pending', 'cash'], true);
                @endphp
                @if($staffCanRecordCash)
                    <form method="POST" action="{{ route('staff.bookings.record-payment', $booking) }}" class="row g-3 align-items-end mb-4" data-confirm="Confirm that the cash payment was received?">
                        @csrf
                        @method('PATCH')
                        @if(!empty($returnTo))
                            <input type="hidden" name="return_to" value="{{ $returnTo }}">
                        @endif
                        <input type="hidden" name="stay_on_booking" value="1">
                        <input type="hidden" name="redirect_section" value="payment-desk">
                        <div class="col-12">
                            <div class="alert alert-light border small mb-0">
                                <strong>Cash payment only.</strong> Record this after the cash has been physically received. Administrators handle online-payment verification.
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Discount</label>
                            <select class="form-select" name="discount_type">
                                <option value="none" @selected(old('discount_type', data_get($booking->reservation_meta, 'discount_type', 'none')) === 'none')>None</option>
                                <option value="pwd" @selected(old('discount_type', data_get($booking->reservation_meta, 'discount_type')) === 'pwd')>PWD (20%)</option>
                                <option value="senior" @selected(old('discount_type', data_get($booking->reservation_meta, 'discount_type')) === 'senior')>Senior (20%)</option>
                                @if(data_get($booking->reservation_meta, 'discount_type') === 'promo')
                                    <option value="promo" @selected(old('discount_type', data_get($booking->reservation_meta, 'discount_type')) === 'promo')>Promo: {{ data_get($booking->reservation_meta, 'promo_code') }} ({{ number_format((float) data_get($booking->reservation_meta, 'promo_discount_percent'), 2) }}%)</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Discount ID Number</label>
                            <input type="text" name="discount_id" class="form-control" maxlength="80" placeholder="PWD/Senior ID" value="{{ old('discount_id', data_get($booking->reservation_meta, 'discount_id')) }}">
                            @if($discountProofUrl !== '')
                                <small class="text-secondary">Uploaded proof available: <a href="{{ $discountProofUrl }}" target="_blank" rel="noopener">View photo</a></small>
                            @endif
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-staff w-100">Record Cash Payment</button>
                        </div>
                    </form>
                @endif

                <div class="booking-meta-line">
                    <span class="booking-meta-label">Current Payment Status</span>
                    <span class="booking-meta-value">{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</span>
                </div>

                @if($booking->payment)
                    @if($latestRefundRequest)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Refund Request</span>
                            <span class="booking-meta-value">{{ $refundRequestStatusLabel }}</span>
                        </div>
                    @endif
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Method</span>
                        <span class="booking-meta-value">{{ \App\Models\Payment::methodLabel($booking->payment->method) }}</span>
                    </div>
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Status</span>
                        <span class="booking-meta-value">{{ ucfirst($booking->payment->status) }}</span>
                    </div>
                    @if($booking->payment_status === 'refund_pending' && $refundMethodLabel)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Refund Method</span>
                            <span class="booking-meta-value">{{ $refundMethodLabel }}</span>
                        </div>
                    @endif
                    @if(filled($booking->payment->customer_reference))
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Customer Ref No.</span>
                            <span class="booking-meta-value">{{ $booking->payment->customer_reference }}</span>
                        </div>
                    @endif
                    @if(filled($booking->payment->qr_reference))
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">QR Reference</span>
                            <span class="booking-meta-value">{{ $booking->payment->qr_reference }}</span>
                        </div>
                    @endif
                    @if($paymentProofUrl !== '')
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Uploaded Proof</span>
                            <span class="booking-meta-value"><a href="{{ $paymentProofUrl }}" target="_blank" rel="noopener">View screenshot</a></span>
                        </div>
                    @endif
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Amount</span>
                        <span class="booking-meta-value">PHP {{ number_format($booking->payment->amount, 2) }}</span>
                    </div>

                    @if(filled(data_get($reservationMeta, 'discount_type')) && (float) ($booking->payment->discount_amount ?? 0) > 0)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Original Amount</span>
                            <span class="booking-meta-value">PHP {{ number_format((float) ($booking->payment->original_amount ?? $booking->total_price), 2) }}</span>
                        </div>
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Discount</span>
                            <span class="booking-meta-value">{{ strtoupper((string) data_get($reservationMeta, 'discount_type')) }} ({{ number_format((float) ($booking->payment->discount_rate ?? 0) * 100, 0) }}%)</span>
                        </div>
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Discount Amount</span>
                            <span class="booking-meta-value">PHP {{ number_format((float) ($booking->payment->discount_amount ?? 0), 2) }}</span>
                        </div>
                    @endif

                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Paid At</span>
                        <span class="booking-meta-value">{{ optional($booking->payment->paid_at)->format('M d, Y h:i A') ?? '-' }}</span>
                    </div>
                    @if($booking->payment->verified_at)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Verified At</span>
                            <span class="booking-meta-value">{{ optional($booking->payment->verified_at)->format('M d, Y h:i A') ?? '-' }}</span>
                        </div>
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Verified By</span>
                            <span class="booking-meta-value">{{ $booking->payment->source === 'paymongo_checkout' ? 'PayMongo' : 'Hotel staff' }}</span>
                        </div>
                    @else
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">Verification</span>
                            <span class="booking-meta-value">Pending verification</span>
                        </div>
                    @endif
                    <div class="booking-meta-line">
                        <span class="booking-meta-label">Transaction Ref</span>
                        <span class="booking-meta-value">{{ $booking->payment->transaction_reference ?? '-' }}</span>
                    </div>
                    @if($booking->payment->provider_payment_id)
                        <div class="booking-meta-line">
                            <span class="booking-meta-label">PayMongo Payment ID</span>
                            <span class="booking-meta-value">{{ $booking->payment->provider_payment_id }}</span>
                        </div>
                    @endif
                    @if($booking->payment_status === 'refund_pending' && $refundMethodLabel)
                        <p class="booking-note mb-0 mt-3">
                            Refund should be processed using the guest's original payment method: <strong>{{ $refundMethodLabel }}</strong>.
                        </p>
                    @endif
                @else
                    <p class="text-secondary mb-0">No payment record yet.</p>
                @endif
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const adultsInput = document.querySelector('#occupancy-update input[name="adults"]');
            const kidsInput = document.querySelector('#occupancy-update input[name="kids"]');
            const beddingGroup = document.getElementById('occupancy_extra_bedding_group');
            const beddingCheckbox = document.getElementById('extra_bedding_confirmed');

            if (!adultsInput || !kidsInput || !beddingGroup || !beddingCheckbox) {
                return;
            }

            const updateExtraBeddingField = () => {
                const totalGuests = Number(adultsInput.value || 0) + Number(kidsInput.value || 0);
                const needsExtraBedding = totalGuests >= 3;
                beddingGroup.classList.toggle('d-none', !needsExtraBedding);
                beddingCheckbox.disabled = !needsExtraBedding;
                beddingCheckbox.required = needsExtraBedding;
                if (!needsExtraBedding) {
                    beddingCheckbox.checked = false;
                }
            };

            adultsInput.addEventListener('input', updateExtraBeddingField);
            kidsInput.addEventListener('input', updateExtraBeddingField);
            updateExtraBeddingField();
        })();
    </script>
@endpush
