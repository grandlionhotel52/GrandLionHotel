@extends('layouts.app')

@section('title', 'Create Booking')

@push('head')
    <style>
        .booking-summary-card {
            border-radius: 22px;
        }
        .booking-summary-image {
            display: block;
            width: 100%;
            height: clamp(220px, 26vw, 300px);
            object-fit: cover;
        }
        .booking-summary-body {
            padding: 1rem 1.25rem 1.25rem;
        }
        .booking-summary-price {
            font-size: clamp(1.45rem, 1.1vw + 1rem, 1.9rem);
        }
        .booking-stepper {
            position: relative;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .booking-step {
            position: relative;
            display: grid;
            grid-template-columns: 2.25rem minmax(0, 1fr);
            gap: .7rem;
            align-items: start;
            color: #475467;
            cursor: default;
        }
        .booking-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 1.1rem;
            left: calc(2.25rem + .35rem);
            right: -1rem;
            height: 1px;
            background: #d9c7aa;
            z-index: 0;
        }
        .booking-step-number {
            position: relative;
            z-index: 1;
            display: grid;
            place-items: center;
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 50%;
            background: #72572f;
            color: #fff;
            font-weight: 800;
        }
        .booking-step:last-child .booking-step-number {
            background: #e9e2d6;
            color: #51452f;
        }
        .booking-step-title {
            display: block;
            margin-top: .05rem;
            color: #263247;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .025em;
            text-transform: uppercase;
        }
        .booking-step-description {
            display: block;
            margin-top: .15rem;
            font-size: .76rem;
            line-height: 1.35;
        }
        .booking-process {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #b89254;
            border-radius: 0 14px 14px 0;
            background: #faf7f1;
        }
        .booking-form-key {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem 1rem;
            padding: .75rem .9rem;
            margin-bottom: 1.25rem;
            border-radius: 12px;
            background: #f7f8fa;
            color: #596273;
            font-size: .8rem;
        }
        .booking-form-key span {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }
        .booking-form-key i {
            color: #72572f;
        }
        .promo-code-box {
            padding: .9rem;
            border: 1px solid #ded4c5;
            border-radius: 14px;
            background: #faf8f4;
        }
        .promo-code-box .input-group .form-control {
            min-width: 0;
        }
        .promo-code-feedback.is-success {
            color: #167347 !important;
            font-weight: 700;
        }
        .promo-code-feedback.is-error {
            color: #a51d16 !important;
            font-weight: 700;
        }
        .booking-estimate {
            border-radius: 16px;
            border: 1px solid #e7dccb;
            background: #fbf5ea;
            padding: 0.85rem 0.9rem;
            margin-top: 0.9rem;
        }
        .booking-estimate-row {
            display: flex;
            justify-content: space-between;
            gap: 0.65rem;
            font-size: 0.86rem;
            margin-bottom: 0.34rem;
            color: #4a5568;
        }
        .booking-estimate-row strong {
            color: #111827;
        }
        .booking-estimate-total {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e2d4bf;
            font-size: 1rem;
            font-weight: 800;
            color: #182235;
        }
        .booking-page-alert {
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            scroll-margin-top: 7rem;
        }
        .booking-page-alert.is-visible {
            animation: booking-alert-in 0.28s ease-out;
        }
        @keyframes booking-alert-in {
            0% {
                transform: translateY(-8px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }
        @media (min-width: 992px) {
            .booking-summary-wrap {
                align-self: flex-start;
                position: sticky;
                top: 5.75rem;
                height: fit-content;
            }
        }
        @media (max-width: 767.98px) {
            .booking-stepper {
                grid-template-columns: 1fr;
            }
            .booking-step:not(:last-child)::after {
                top: 2.25rem;
                bottom: -1rem;
                left: 1.1rem;
                right: auto;
                width: 1px;
                height: auto;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $nameParts = preg_split('/\s+/', trim($user->name ?? ''), 2);
        $defaultFirstName = $nameParts[0] ?? '';
        $defaultLastName = $nameParts[1] ?? '';
        $provinces = config('philippines.provinces', []);
        $standardGuests = \App\Models\Room::standardGuestCapacity();

        $prefill = $prefill ?? [
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'guests' => $standardGuests,
            'adults' => $standardGuests,
            'kids' => 0,
            'minimum_check_in' => now()->toDateString(),
            'minimum_check_out' => now()->addDay()->toDateString(),
            'has_date_selection' => false,
            'date_selection_valid' => false,
            'unavailable_for_selected_dates' => false,
            'availability_message' => null,
        ];
        $pricingPreview = $pricingPreview ?? null;

        $initialCheckIn = old('check_in', $prefill['check_in']);
        $initialCheckOut = old('check_out', $prefill['check_out']);
        $initialGuests = $standardGuests;
        $initialAdults = $standardGuests;
        $initialKids = 0;

        $minimumCheckIn = $prefill['minimum_check_in'];
        $minimumCheckOut = $prefill['minimum_check_out'];
        if (filled($initialCheckIn)) {
            try {
                $minimumCheckOut = \Carbon\Carbon::parse($initialCheckIn)->addDay()->toDateString();
            } catch (\Throwable) {
                $minimumCheckOut = $prefill['minimum_check_out'];
            }
        }

        $initialSummaryStay = '--';
        if ($pricingPreview) {
            $initialSummaryStay = \Carbon\Carbon::parse($pricingPreview['check_in'])->format('M d, Y')
                .' - '
                .\Carbon\Carbon::parse($pricingPreview['check_out'])->format('M d, Y');
        }

        $initialSummaryUnits = $pricingPreview
            ? $pricingPreview['nights'].' night'.($pricingPreview['nights'] === 1 ? '' : 's')
            : '--';
        $initialSummaryRate = $pricingPreview
            ? 'PHP '.number_format($pricingPreview['average_nightly_rate'], 2).' / night'
            : 'PHP '.number_format((float) $room->price_per_night, 2).' / night';
        $initialSummaryDiscount = $pricingPreview && $pricingPreview['has_date_discount']
            ? 'Date discount on '.$pricingPreview['discounted_nights'].' night'.($pricingPreview['discounted_nights'] === 1 ? '' : 's')
            : 'None selected';
        $initialSummaryAvailability = $prefill['date_selection_valid']
            ? ($prefill['unavailable_for_selected_dates'] ? 'Unavailable for selected dates' : 'Available for selected dates')
            : 'Checking selected dates...';
    @endphp

    <div class="row g-4">
        <div class="col-lg-4 booking-summary-wrap">
            <aside class="soft-card overflow-hidden booking-summary-card">
                <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="booking-summary-image">
                <div class="booking-summary-body">
                    <h2 class="h5 mb-1">{{ $room->name }}</h2>
                    <p class="hotel-meta mb-2">
                        {{ $room->type }}
                        @if(filled($room->view_type))
                            &middot; {{ $room->view_type }}
                        @endif
                        &middot; Standard occupancy: {{ $standardGuests }} guests
                    </p>
                    <div class="price-tag booking-summary-price" id="summary_headline_rate">
                        &#8369;{{ number_format($pricingPreview['average_nightly_rate'] ?? $room->price_per_night, 2) }}
                    </div>
                    <small class="text-secondary d-block" id="summary_price_caption">
                        {{ $pricingPreview ? 'average per night' : 'per night' }}
                    </small>
                    <p class="small mt-1 mb-0 {{ $pricingPreview && $pricingPreview['has_date_discount'] ? '' : 'd-none' }}" id="summary_base_rate_wrap">
                        <span class="text-secondary text-decoration-line-through" id="summary_base_rate">
                            &#8369;{{ number_format($room->price_per_night, 2) }}
                        </span>
                        <span class="text-success ms-2" id="summary_savings_note">
                            @if($pricingPreview && $pricingPreview['has_date_discount'])
                                Date discount saves &#8369;{{ number_format($pricingPreview['discount_amount'], 2) }}
                            @endif
                        </span>
                    </p>

                    <div class="booking-estimate">
                        <p class="ta-eyebrow mb-2">Live Estimate</p>
                        <div class="booking-estimate-row"><span>Stay</span><strong id="summary_stay">{{ $initialSummaryStay }}</strong></div>
                        <div class="booking-estimate-row"><span id="summary_units_label">Nights</span><strong id="summary_units">{{ $initialSummaryUnits }}</strong></div>
                        <div class="booking-estimate-row"><span>Rate</span><strong id="summary_rate">{{ $initialSummaryRate }}</strong></div>
                        <div class="booking-estimate-row"><span>Standard occupancy</span><strong id="summary_guests">{{ $initialGuests }} guests</strong></div>
                        <div class="booking-estimate-row"><span>Discount</span><strong id="summary_discount">{{ $initialSummaryDiscount }}</strong></div>
                        <div class="booking-estimate-row"><span>Availability</span><strong id="summary_availability">{{ $initialSummaryAvailability }}</strong></div>
                        <div class="booking-estimate-row booking-estimate-total mb-0">
                            <span>Estimated total</span>
                            <strong id="summary_total">&#8369;{{ number_format($pricingPreview['total'] ?? $room->price_per_night, 2) }}</strong>
                        </div>
                        <small class="text-secondary d-block mt-2" id="summary_discount_note">
                            Automatic date discounts are included when available. PWD/Senior discounts require staff verification.
                        </small>
                    </div>
                </div>
            </aside>
        </div>

        <div class="col-lg-8">
            <section class="soft-card p-4 p-lg-5">
                <div class="booking-process" role="note" aria-labelledby="booking_process_title">
                    <p class="small fw-bold text-uppercase mb-3" id="booking_process_title">How booking works <span class="text-secondary fw-normal">&mdash; information only</span></p>
                    <ol class="booking-stepper">
                        <li class="booking-step">
                            <span class="booking-step-number">1</span>
                            <span><span class="booking-step-title">Enter guest details</span><span class="booking-step-description">Fill in the form below.</span></span>
                        </li>
                        <li class="booking-step">
                            <span class="booking-step-number">2</span>
                            <span><span class="booking-step-title">Choose your stay</span><span class="booking-step-description">Select guests, dates, and preferences.</span></span>
                        </li>
                        <li class="booking-step">
                            <span class="booking-step-number">3</span>
                            <span><span class="booking-step-title">Hotel reviews</span><span class="booking-step-description">Staff confirms availability after submission.</span></span>
                        </li>
                    </ol>
                </div>

                <p class="ta-eyebrow mb-1">Pre-booking</p>
                <h1 class="h3 mb-2">Pre-book your stay</h1>
                <p class="text-secondary mb-4">Submit your request for hotel review. This will not be confirmed until staff approves it.</p>

                <div class="booking-form-key" aria-label="Form guide">
                    <span><i class="bi bi-pencil-square" aria-hidden="true"></i>White fields can be filled in</span>
                    <span><strong class="text-danger">*</strong> Required</span>
                    <span><i class="bi bi-info-circle" aria-hidden="true"></i>Fields without * are optional</span>
                </div>

                @if(!empty($prefill['availability_message']))
                    <div id="booking_prefill_feedback" class="alert booking-page-alert {{ $prefill['unavailable_for_selected_dates'] ? 'alert-warning' : 'alert-info' }}" role="alert" tabindex="-1">
                        {{ $prefill['availability_message'] }}
                    </div>
                @endif

                <div id="booking_ajax_feedback" class="alert alert-danger booking-page-alert d-none" role="alert" tabindex="-1" aria-live="assertive"></div>

                <form
                    id="booking_form"
                    method="POST"
                    action="{{ route('bookings.store') }}"
                    class="row g-3"
                    enctype="multipart/form-data"
                    data-no-submit-lock
                    data-base-nightly-rate="{{ number_format((float) $room->price_per_night, 2, '.', '') }}"
                    data-preview-url="{{ route('rooms.pricing-preview', $room) }}"
                >
                    @csrf
                    <input type="hidden" name="room_id" value="{{ $room->id }}">
                    <input type="hidden" id="guests_input" name="guests" value="{{ $initialGuests }}">
                    <input type="hidden" id="adults_input" name="adults" value="{{ $initialAdults }}">
                    <input type="hidden" id="kids_input" name="kids" value="{{ $initialKids }}">

                    <div class="col-12 pt-1">
                        <h2 class="h5 mb-1">Guest Information</h2>
                        <p class="small text-secondary mb-0">Who is staying?</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">First name</label>
                        <input type="text" class="form-control" name="first_name" value="{{ old('first_name', $defaultFirstName) }}" maxlength="80">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Last name</label>
                        <input type="text" class="form-control" name="last_name" value="{{ old('last_name', $defaultLastName) }}" maxlength="80">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Street address</label>
                        <input type="text" class="form-control" name="street_address" value="{{ old('street_address', $user->address_line) }}" maxlength="255">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Street address line 2</label>
                        <input type="text" class="form-control" name="street_address_line_2" value="{{ old('street_address_line_2') }}" maxlength="255" placeholder="Optional">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="guest_city" value="{{ old('guest_city', $user->city) }}" maxlength="120">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">State / Province</label>
                        <input type="text" list="province-list" class="form-control" name="state_province" value="{{ old('state_province', $user->province) }}" maxlength="120" autocomplete="off" placeholder="Start typing to search province">
                        <datalist id="province-list">
                            @foreach($provinces as $province)
                                <option value="{{ $province }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Postal / Zip code</label>
                        <input type="text" class="form-control" name="postal_code" value="{{ old('postal_code') }}" maxlength="40">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone number</label>
                        <input type="text" class="form-control" name="contact_phone" value="{{ old('contact_phone', $user->phone) }}" maxlength="30" placeholder="+63...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" name="contact_email" value="{{ old('contact_email', $user->email) }}" maxlength="255">
                    </div>

                    <div class="col-12 pt-2">
                        <h2 class="h5 mb-1">Stay Schedule</h2>
                        <p class="small text-secondary mb-0">Set your stay dates</p>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Arrival date</label>
                        <input type="date" class="form-control" id="check_in_input" name="check_in" required min="{{ $minimumCheckIn }}" value="{{ $initialCheckIn }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Departure date</label>
                        <input type="date" class="form-control" id="check_out_input" name="check_out" required min="{{ $minimumCheckOut }}" value="{{ $initialCheckOut }}">
                    </div>
                    <div class="col-12" id="nightly_time_policy_note">
                    </div>
                    <div class="col-12 pt-2">
                        <h2 class="h5 mb-1">Discount and Special Requests</h2>
                        <p class="small text-secondary mb-0">Choose your payment method after the hotel confirms your booking.</p>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="meal_plan_select">Meal option</label>
                        <select class="form-select" name="meal_plan" id="meal_plan_select" required>
                            <option value="room_only" @selected(old('meal_plan', 'room_only') === 'room_only')>Room Only — No Breakfast</option>
                            <option value="breakfast_included" @selected(old('meal_plan') === 'breakfast_included')>Breakfast Included</option>
                        </select>
                        <small class="text-secondary">Your selection will be included in the booking details. Any applicable meal charge will be confirmed by the hotel.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discount type</label>
                        <select class="form-select" name="discount_type" id="discount_type_select">
                            <option value="none" @selected(old('discount_type', 'none') === 'none')>None</option>
                            <option value="pwd" @selected(old('discount_type') === 'pwd')>PWD (20%)</option>
                            <option value="senior" @selected(old('discount_type') === 'senior')>Senior (20%)</option>
                            <option value="promo" @selected(old('discount_type') === 'promo')>Promotional Code</option>
                        </select>
                    </div>

                    <div class="col-md-4 {{ in_array(old('discount_type'), ['pwd', 'senior'], true) ? '' : 'd-none' }}" id="discount_id_group">
                        <label class="form-label">Discount ID</label>
                        <input type="text" class="form-control" name="discount_id" id="discount_id_input" maxlength="80" value="{{ old('discount_id') }}" placeholder="PWD/Senior ID number">
                    </div>

                    <div class="col-md-4 {{ in_array(old('discount_type'), ['pwd', 'senior'], true) ? '' : 'd-none' }}" id="discount_photo_group">
                        <label class="form-label">Discount ID photo</label>
                        <input type="file" class="form-control" name="discount_id_photo" id="discount_id_photo_input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small class="text-secondary">Required for PWD/Senior discount.</small>
                    </div>

                    <div class="col-md-8" id="promo_code_group">
                        <div class="promo-code-box">
                            <label class="form-label fw-semibold" for="promo_code_input"><i class="bi bi-tag me-1" aria-hidden="true"></i>Have a promo code? <span class="text-secondary fw-normal">(optional)</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control text-uppercase" name="promo_code" id="promo_code_input" maxlength="40" value="{{ old('promo_code') }}" placeholder="Enter code" autocomplete="off" aria-describedby="promo_code_feedback">
                                <button type="button" class="btn btn-ta" id="promo_code_apply">Apply code</button>
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mt-2">
                                <small class="text-secondary promo-code-feedback" id="promo_code_feedback" aria-live="polite">Enter a code, then select Apply code.</small>
                                <button type="button" class="btn btn-link btn-sm text-danger p-0 d-none" id="promo_code_remove">Remove</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Special request (optional)</label>
                        <textarea class="form-control" name="notes" rows="3" maxlength="500" placeholder="Example: extra bed or accessibility needs">{{ old('notes') }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-light border mb-0 small">
                            This is a pre-booking request only. It is not confirmed until hotel staff approves it.
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <x-back-button :href="route('rooms.show', $room)" label="Back to room" />
                        <button type="submit" class="btn btn-ta">Submit pre-booking</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('booking_form');
            const guestsInput = document.getElementById('guests_input');
            const adultsInput = document.getElementById('adults_input');
            const kidsInput = document.getElementById('kids_input');
            const checkInInput = document.getElementById('check_in_input');
            const checkOutInput = document.getElementById('check_out_input');
            const ajaxFeedback = document.getElementById('booking_ajax_feedback');
            const prefillFeedback = document.getElementById('booking_prefill_feedback');
            const discountTypeSelect = document.getElementById('discount_type_select');
            const discountIdInput = document.getElementById('discount_id_input');
            const discountIdPhotoInput = document.getElementById('discount_id_photo_input');
            const discountIdGroup = document.getElementById('discount_id_group');
            const discountPhotoGroup = document.getElementById('discount_photo_group');
            const promoCodeInput = document.getElementById('promo_code_input');
            const promoCodeGroup = document.getElementById('promo_code_group');
            const promoCodeFeedback = document.getElementById('promo_code_feedback');
            const promoCodeApply = document.getElementById('promo_code_apply');
            const promoCodeRemove = document.getElementById('promo_code_remove');
            const promoCodes = @json($activePromoCodes->mapWithKeys(fn ($promo) => [$promo->code => (float) $promo->discount_percent]));
            let appliedPromoCode = '';
            const standardGuests = {{ $standardGuests }};
            const baseNightlyRate = Number.parseFloat(form?.dataset.baseNightlyRate || '0') || 0;
            const submitButton = form?.querySelector('button[type="submit"]');
            const defaultSubmitText = submitButton?.textContent?.trim() || 'Submit pre-booking';
            let currentPricing = @json($pricingPreview);
            let currentAvailability = null;

            if (!form || !guestsInput || !adultsInput || !kidsInput || !checkInInput || !checkOutInput) {
                return;
            }

            const summaryHeadlineRate = document.getElementById('summary_headline_rate');
            const summaryPriceCaption = document.getElementById('summary_price_caption');
            const summaryBaseRateWrap = document.getElementById('summary_base_rate_wrap');
            const summaryBaseRate = document.getElementById('summary_base_rate');
            const summarySavingsNote = document.getElementById('summary_savings_note');
            const summaryStay = document.getElementById('summary_stay');
            const summaryUnitsLabel = document.getElementById('summary_units_label');
            const summaryUnits = document.getElementById('summary_units');
            const summaryRate = document.getElementById('summary_rate');
            const summaryGuests = document.getElementById('summary_guests');
            const summaryDiscount = document.getElementById('summary_discount');
            const summaryDiscountNote = document.getElementById('summary_discount_note');
            const summaryAvailability = document.getElementById('summary_availability');
            const summaryTotal = document.getElementById('summary_total');
            const dateFormatter = new Intl.DateTimeFormat('en-PH', {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
            });

            const formatCurrency = (value) => new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                maximumFractionDigits: 2,
            }).format(Math.max(0, value));

            const selectedIdentityDiscountRate = () => {
                if (discountTypeSelect?.value === 'pwd' || discountTypeSelect?.value === 'senior') return 0.20;
                if (discountTypeSelect?.value !== 'promo') return 0;
                const code = (promoCodeInput?.value || '').trim().toUpperCase();
                if (code === '' || appliedPromoCode !== code) return 0;
                return Number(promoCodes[code] || 0) / 100;
            };

            const parseDate = (value) => {
                if (!value) {
                    return null;
                }
                const parsed = new Date(`${value}T00:00:00`);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            };

            const formatInputDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            const nightsBetween = (start, end) => {
                const startDate = parseDate(start);
                const endDate = parseDate(end);
                if (!startDate || !endDate) {
                    return 0;
                }

                const diff = Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
                return diff > 0 ? diff : 0;
            };

            const scrollToElement = (element) => {
                if (!element) {
                    return;
                }

                const top = window.scrollY + element.getBoundingClientRect().top - 110;
                window.scrollTo({
                    top: Math.max(0, top),
                    behavior: 'smooth',
                });
            };

            const focusElement = (element) => {
                if (!element || typeof element.focus !== 'function') {
                    return;
                }

                window.setTimeout(() => {
                    element.focus({ preventScroll: true });
                }, 180);
            };

            const updateDateRules = () => {
                const checkInDate = parseDate(checkInInput.value);
                if (!checkInDate) {
                    return;
                }

                const minCheckoutDate = new Date(checkInDate);
                minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);
                const minCheckoutValue = formatInputDate(minCheckoutDate);
                checkOutInput.min = minCheckoutValue;

                if (!checkOutInput.value || checkOutInput.value <= checkInInput.value) {
                    checkOutInput.value = minCheckoutValue;
                }
            };

            const syncStandardGuests = () => {
                adultsInput.value = String(standardGuests);
                kidsInput.value = '0';
                guestsInput.value = String(standardGuests);

                if (summaryGuests) {
                    summaryGuests.textContent = `${standardGuests} guests`;
                }
            };

            const updateAvailabilityState = (availability, fallbackMessage = 'Select valid dates to preview.') => {
                currentAvailability = availability;

                if (summaryAvailability) {
                    summaryAvailability.textContent = availability?.message || fallbackMessage;
                }

                if (submitButton) {
                    const canSubmit = availability?.stay_available ?? false;
                    submitButton.disabled = !canSubmit;
                    submitButton.textContent = canSubmit ? defaultSubmitText : 'Select available stay dates';
                }
            };

            const updateSummaryDiscountText = () => {
                if (!summaryDiscount) {
                    return;
                }

                const segments = [];
                if (currentPricing?.has_date_discount) {
                    segments.push(
                        `Date discount on ${currentPricing.discounted_nights} night${currentPricing.discounted_nights === 1 ? '' : 's'}`
                    );
                }

                const requiresId = discountTypeSelect
                    && (discountTypeSelect.value === 'pwd' || discountTypeSelect.value === 'senior');

                if (requiresId) {
                    const provisionalBase = Number(currentPricing?.total || 0);
                    const provisionalDiscount = provisionalBase * 0.20;
                    segments.push(`${discountTypeSelect.value.toUpperCase()} 20% discount (-${formatCurrency(provisionalDiscount)}, subject to verification)`);
                }
                if (discountTypeSelect?.value === 'promo') {
                    const code = (promoCodeInput?.value || '').trim().toUpperCase();
                    const rate = selectedIdentityDiscountRate();
                    segments.push(rate > 0
                        ? `${code} promotional discount (${Number(promoCodes[code])}% / -${formatCurrency(Number(currentPricing?.total || 0) * rate)})`
                        : 'Enter a valid promotional code');
                }

                summaryDiscount.textContent = segments.length > 0 ? segments.join(' + ') : 'None selected';

                if (summaryDiscountNote) {
                    const notes = [];
                    if (currentPricing?.has_date_discount) {
                        notes.push('Automatic date discount is included in this estimate.');
                    }
                    if (requiresId) {
                        notes.push(`${discountTypeSelect.value.toUpperCase()} discount is estimated and requires staff ID verification.`);
                    } else if (discountTypeSelect?.value === 'promo' && selectedIdentityDiscountRate() > 0) {
                        notes.push('The applied promo code is included in this estimate.');
                    }
                    summaryDiscountNote.textContent = notes.length > 0 ? notes.join(' ') : 'No discount is currently applied.';
                }
            };

            const renderPricingSummary = (pricing = null, availability = null) => {
                const checkInDate = parseDate(checkInInput.value);
                const checkOutDate = parseDate(checkOutInput.value);
                const nights = nightsBetween(checkInInput.value, checkOutInput.value);
                const rate = pricing?.average_nightly_rate ?? baseNightlyRate;
                const subtotal = pricing?.total ?? (nights > 0 ? nights * baseNightlyRate : 0);
                const identityDiscount = subtotal * selectedIdentityDiscountRate();
                const total = Math.max(0, subtotal - identityDiscount);

                if (summaryStay) {
                    if (checkInDate && checkOutDate) {
                        summaryStay.textContent = `${dateFormatter.format(checkInDate)} - ${dateFormatter.format(checkOutDate)}`;
                    } else {
                        summaryStay.textContent = '--';
                    }
                }

                if (summaryUnits) {
                    summaryUnits.textContent = nights > 0 ? `${nights} night${nights === 1 ? '' : 's'}` : '--';
                }

                if (summaryRate) {
                    summaryRate.textContent = rate > 0 ? `${formatCurrency(rate)} / night` : '--';
                }

                if (summaryUnitsLabel) {
                    summaryUnitsLabel.textContent = 'Nights';
                }

                if (summaryHeadlineRate) {
                    summaryHeadlineRate.textContent = formatCurrency(rate);
                }

                if (summaryPriceCaption) {
                    summaryPriceCaption.textContent = pricing ? 'average per night' : 'per night';
                }

                if (summaryBaseRate) {
                    summaryBaseRate.textContent = formatCurrency(baseNightlyRate);
                }

                if (summaryBaseRateWrap) {
                    summaryBaseRateWrap.classList.toggle('d-none', !pricing?.has_date_discount);
                }

                if (summarySavingsNote) {
                    summarySavingsNote.textContent = pricing?.has_date_discount
                        ? `Date discount saves ${formatCurrency(pricing.discount_amount)}`
                        : '';
                }

                if (summaryTotal) {
                    summaryTotal.textContent = formatCurrency(total);
                }

                updateSummaryDiscountText();
                updateAvailabilityState(availability, nights > 0
                    ? 'Checking selected dates...'
                    : 'Select valid dates to preview.');
            };

            const refreshPricingPreview = async () => {
                const checkInDate = parseDate(checkInInput.value);
                const checkOutDate = parseDate(checkOutInput.value);

                if (!checkInDate || !checkOutDate || checkOutDate <= checkInDate) {
                    currentPricing = null;
                    renderPricingSummary(null, null);
                    return;
                }

                try {
                    const previewUrl = new URL(form.dataset.previewUrl, window.location.origin);
                    previewUrl.searchParams.set('check_in', checkInInput.value);
                    previewUrl.searchParams.set('check_out', checkOutInput.value);

                    const response = await fetch(previewUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => null);

                    if (!response.ok || !payload?.pricing) {
                        currentPricing = null;
                        renderPricingSummary(null, null);

                        if (summaryAvailability) {
                            summaryAvailability.textContent = payload?.message || 'Unable to load pricing right now.';
                        }

                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.textContent = 'Select available stay dates';
                        }

                        return;
                    }

                    currentPricing = payload.pricing;
                    renderPricingSummary(payload.pricing, payload.availability);
                } catch (error) {
                    currentPricing = null;
                    renderPricingSummary(null, null);

                    if (summaryAvailability) {
                        summaryAvailability.textContent = 'Unable to load pricing right now.';
                    }

                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.textContent = 'Select available stay dates';
                    }
                }
            };

            const clearFormErrors = () => {
                form.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback.dynamic').forEach((node) => node.remove());
            };

            const showAlert = (message) => {
                if (!ajaxFeedback) {
                    return;
                }

                ajaxFeedback.textContent = message;
                ajaxFeedback.classList.remove('d-none');
                ajaxFeedback.classList.add('is-visible');
                scrollToElement(ajaxFeedback);
                focusElement(ajaxFeedback);

                window.setTimeout(() => {
                    ajaxFeedback.classList.remove('is-visible');
                }, 700);
            };

            const clearAlert = () => {
                if (!ajaxFeedback) {
                    return;
                }

                ajaxFeedback.textContent = '';
                ajaxFeedback.classList.add('d-none');
                ajaxFeedback.classList.remove('is-visible');
            };

            const setFieldError = (field, messages) => {
                const input = form.querySelector(`[name="${field}"]`);
                const primaryMessage = Array.isArray(messages) ? messages[0] : messages;

                if (!input || input.type === 'hidden') {
                    if (primaryMessage) {
                        showAlert(primaryMessage);
                    }

                    return;
                }

                input.classList.add('is-invalid');

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback dynamic';
                feedback.textContent = primaryMessage || 'Invalid value.';
                input.parentElement.appendChild(feedback);
            };

            const showFirstErrorInView = () => {
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    scrollToElement(firstInvalid);
                    focusElement(firstInvalid);
                    return;
                }

                if (ajaxFeedback && !ajaxFeedback.classList.contains('d-none')) {
                    scrollToElement(ajaxFeedback);
                    focusElement(ajaxFeedback);
                }
            };

            const updateDiscountState = () => {
                if (!discountTypeSelect || !discountIdInput) {
                    return;
                }

                const requiresId = discountTypeSelect.value === 'pwd' || discountTypeSelect.value === 'senior';
                const requiresPromo = discountTypeSelect.value === 'promo';
                discountIdInput.required = requiresId;
                discountIdInput.disabled = !requiresId;
                discountIdGroup?.classList.toggle('d-none', !requiresId);
                discountPhotoGroup?.classList.toggle('d-none', !requiresId);
                if (discountIdPhotoInput) {
                    discountIdPhotoInput.required = requiresId;
                    discountIdPhotoInput.disabled = !requiresId;
                }
                if (promoCodeInput) {
                    promoCodeInput.required = requiresPromo;
                }

                if (!requiresId) {
                    discountIdInput.value = '';
                    if (discountIdPhotoInput) {
                        discountIdPhotoInput.value = '';
                    }
                }

                renderPricingSummary(currentPricing, currentAvailability);
            };

            const syncAll = () => {
                updateDateRules();
                syncStandardGuests();
                renderPricingSummary(currentPricing, currentAvailability);
                updateDiscountState();
            };

            checkInInput.addEventListener('change', () => {
                updateDateRules();
                refreshPricingPreview();
            });
            checkOutInput.addEventListener('change', refreshPricingPreview);
            discountTypeSelect?.addEventListener('change', () => {
                if (discountTypeSelect.value !== 'promo' && appliedPromoCode !== '') {
                    appliedPromoCode = '';
                    if (promoCodeInput) promoCodeInput.value = '';
                    promoCodeRemove?.classList.add('d-none');
                    promoCodeFeedback?.classList.remove('is-success', 'is-error');
                    if (promoCodeFeedback) promoCodeFeedback.textContent = 'Enter a code, then select Apply code.';
                }
                updateDiscountState();
            });
            promoCodeInput?.addEventListener('input', () => {
                promoCodeInput.value = promoCodeInput.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '').slice(0, 40);
                if (appliedPromoCode !== promoCodeInput.value) {
                    appliedPromoCode = '';
                    promoCodeRemove?.classList.add('d-none');
                }
                promoCodeInput.setCustomValidity('');
                promoCodeFeedback?.classList.remove('is-success', 'is-error');
                if (promoCodeFeedback) promoCodeFeedback.textContent = promoCodeInput.value === '' ? 'Enter a code, then select Apply code.' : 'Select Apply code to check this promotion.';
                renderPricingSummary(currentPricing, currentAvailability);
            });

            promoCodeApply?.addEventListener('click', () => {
                const code = (promoCodeInput?.value || '').trim().toUpperCase();
                const discountPercent = Number(promoCodes[code] || 0);
                promoCodeFeedback?.classList.remove('is-success', 'is-error');

                if (code === '' || discountPercent <= 0) {
                    appliedPromoCode = '';
                    promoCodeInput?.setCustomValidity('Enter a valid active promotional code.');
                    promoCodeFeedback?.classList.add('is-error');
                    if (promoCodeFeedback) promoCodeFeedback.textContent = code === '' ? 'Enter a promo code first.' : 'This promo code is invalid, inactive, or expired.';
                    promoCodeRemove?.classList.add('d-none');
                    promoCodeInput?.reportValidity();
                    renderPricingSummary(currentPricing, currentAvailability);
                    return;
                }

                appliedPromoCode = code;
                promoCodeInput.value = code;
                promoCodeInput.setCustomValidity('');
                if (discountTypeSelect) discountTypeSelect.value = 'promo';
                updateDiscountState();
                promoCodeFeedback?.classList.add('is-success');
                if (promoCodeFeedback) promoCodeFeedback.textContent = `${discountPercent}% discount applied successfully.`;
                promoCodeRemove?.classList.remove('d-none');
                renderPricingSummary(currentPricing, currentAvailability);
            });

            promoCodeRemove?.addEventListener('click', () => {
                appliedPromoCode = '';
                if (promoCodeInput) {
                    promoCodeInput.value = '';
                    promoCodeInput.required = false;
                    promoCodeInput.setCustomValidity('');
                }
                if (discountTypeSelect?.value === 'promo') discountTypeSelect.value = 'none';
                promoCodeFeedback?.classList.remove('is-success', 'is-error');
                if (promoCodeFeedback) promoCodeFeedback.textContent = 'Promo code removed. Enter another code if you have one.';
                promoCodeRemove.classList.add('d-none');
                updateDiscountState();
            });

            syncAll();
            refreshPricingPreview();

            if (prefillFeedback && prefillFeedback.classList.contains('alert-warning')) {
                prefillFeedback.classList.add('is-visible');
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const enteredPromoCode = (promoCodeInput?.value || '').trim().toUpperCase();
                if (discountTypeSelect?.value === 'promo' && appliedPromoCode !== enteredPromoCode) {
                    promoCodeInput?.setCustomValidity('Select Apply code before submitting your booking.');
                    promoCodeFeedback?.classList.remove('is-success');
                    promoCodeFeedback?.classList.add('is-error');
                    if (promoCodeFeedback) promoCodeFeedback.textContent = 'Select Apply code before submitting your booking.';
                    promoCodeInput?.reportValidity();
                    promoCodeInput?.focus();
                    return;
                }

                if (form.dataset.submitting === '1') {
                    return;
                }

                form.dataset.submitting = '1';
                const originalButtonText = submitButton ? submitButton.textContent : defaultSubmitText;
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Submitting request...';
                }

                await refreshPricingPreview();
                syncAll();
                clearFormErrors();
                clearAlert();

                if (!currentAvailability?.stay_available) {
                    showAlert(currentAvailability?.message || 'Select available stay dates before submitting your booking.');
                    delete form.dataset.submitting;
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText || defaultSubmitText;
                    }
                    return;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const isJson = (response.headers.get('content-type') || '').includes('application/json');
                    const payload = isJson ? await response.json() : null;

                    if (response.ok) {
                        if (payload && payload.redirect) {
                            window.location.href = payload.redirect;
                            return;
                        }

                        if (response.redirected) {
                            window.location.href = response.url;
                            return;
                        }

                        window.location.reload();
                        return;
                    }

                    if (payload && payload.redirect && response.status === 422) {
                        window.location.href = payload.redirect;
                        return;
                    }

                    if (response.status === 422 && payload && payload.errors) {
                        let firstErrorMessage = null;

                        Object.entries(payload.errors).forEach(([field, messages]) => {
                            if (!firstErrorMessage) {
                                firstErrorMessage = Array.isArray(messages) ? messages[0] : messages;
                            }
                            setFieldError(field, messages);
                        });

                        if (firstErrorMessage) {
                            showAlert(firstErrorMessage);
                        }

                        showFirstErrorInView();
                        return;
                    }

                    showAlert((payload && payload.message) ? payload.message : 'Unable to submit booking right now. Please try again.');
                } catch (error) {
                    showAlert('Network error. Please check your connection and try again.');
                } finally {
                    delete form.dataset.submitting;
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText || 'Submit pre-booking';
                    }
                }
            });
        })();
    </script>
@endpush
