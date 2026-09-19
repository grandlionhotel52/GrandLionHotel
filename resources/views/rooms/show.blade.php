@extends('layouts.app')

@section('title', $room->name)

@push('head')
    <style>
        .room-hero-image {
            width: 100%;
            height: clamp(280px, 46vw, 500px);
            object-fit: cover;
        }
        .room-type-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            border: 1px solid rgba(184, 146, 84, 0.36);
            background: rgba(184, 146, 84, 0.12);
            color: #75582e;
            padding: 0.26rem 0.72rem;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }
        .room-feature-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.6rem;
        }
        .room-feature {
            border-radius: 12px;
            border: 1px solid #ebdfcd;
            background: #fff;
            padding: 0.65rem 0.75rem;
            font-size: 0.86rem;
            color: #374151;
            font-weight: 600;
        }
        .room-booking-panel {
            border-radius: 20px;
            border: 1px solid var(--line);
            background: #fbf6ed;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.1);
            position: static;
            z-index: 1;
        }
        .room-pricing-locked {
            display: flex;
            gap: .7rem;
            margin: 1rem 0 1.25rem;
            padding: .85rem;
            border: 1px solid #dfd1ba;
            border-radius: 14px;
            background: rgba(255, 255, 255, .72);
            color: #4b5563;
        }
        .room-pricing-locked i {
            color: #87662f;
            font-size: 1.05rem;
        }
        .room-date-trigger {
            width: 100%;
            min-height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #dbc9ae;
            border-radius: 16px;
            background: #fff;
            color: #263247;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            text-align: left;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .room-date-trigger:hover,
        .room-date-trigger:focus-visible,
        .room-date-trigger[aria-expanded="true"] {
            border-color: var(--theme-primary);
            box-shadow: 0 0 0 .2rem rgba(var(--theme-primary-rgb), .14);
            outline: 0;
        }
        .room-date-trigger.is-unavailable {
            border: 2px solid #c62828;
            background: #fff8f8;
            color: #9f1f1f;
            box-shadow: 0 0 0 .18rem rgba(198, 40, 40, .12);
        }
        .room-availability-calendar {
            border: 1px solid #dbc9ae;
            border-radius: 16px;
            background: #fff;
            padding: .85rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        }
        .room-calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .65rem;
            margin-bottom: .65rem;
        }
        .room-calendar-nav {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid #dbc9ae;
            background: #fff;
            color: #263247;
        }
        .room-calendar-weekdays,
        .room-calendar-days {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: .25rem;
        }
        .room-calendar-weekdays span {
            color: #687386;
            font-size: .7rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
        }
        .room-calendar-day {
            aspect-ratio: 1;
            min-width: 0;
            border: 1px solid transparent;
            border-radius: 9px;
            background: #fff;
            color: #263247;
            font-size: .8rem;
            font-weight: 700;
        }
        .room-calendar-day:hover:not(:disabled),
        .room-calendar-day:focus-visible {
            border-color: var(--theme-primary);
            background: rgba(var(--theme-primary-rgb), .1);
            outline: 0;
        }
        .room-calendar-day.is-unavailable {
            border-color: #e6a2a2;
            background: #fff0f0;
            color: #c62828;
            text-decoration: line-through;
        }
        .room-calendar-day.is-selected {
            border-color: #8b6427;
            background: #8b6427;
            color: #fff;
            text-decoration: none;
        }
        .room-calendar-day.is-unavailable.is-selected {
            border-color: #c62828;
            background: #c62828;
            color: #fff;
        }
        .room-calendar-day:disabled:not(.is-unavailable) {
            color: #b4bac3;
            background: #f7f7f7;
        }
        .room-calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: .8rem;
            margin-top: .7rem;
            color: #687386;
            font-size: .72rem;
        }
        .room-calendar-legend-mark {
            width: .7rem;
            height: .7rem;
            display: inline-block;
            border-radius: 3px;
            margin-right: .3rem;
            vertical-align: -.05rem;
        }
        .room-calendar-legend-mark.unavailable {
            border: 1px solid #e6a2a2;
            background: #fff0f0;
        }
        @media (min-width: 992px) {
            .room-booking-panel {
                position: sticky;
                top: 78px;
                max-height: calc(100vh - 78px);
                overflow-y: auto;
            }
        }
        @media (max-width: 575.98px) {
            .room-feature-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 991.98px) {
            .room-booking-panel {
                max-height: none;
                overflow: visible;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $stay = $stay ?? [
            'check_in' => null,
            'check_out' => null,
            'nights' => null,
            'is_valid' => false,
        ];
        $pricingPreview = $pricingPreview ?? null;
        $stayAvailability = $stayAvailability ?? $room->is_available;
        $checkIn = (string) request('check_in', now()->toDateString());
        $checkOut = (string) request('check_out', now()->addDay()->toDateString());
        $standardGuests = \App\Models\Room::standardGuestCapacity();
        if ($checkOut === '') {
            $checkOut = now()->addDay()->toDateString();
        }
        $minimumCheckOut = now()->addDay()->toDateString();
        $viewer = request()->user();
        $showDetailedPricing = (bool) $viewer;
        $canStartCustomerBooking = !$viewer || $viewer->isCustomer();
        $bookingButtonLabel = $viewer ? 'Continue' : 'Sign in and continue';
    @endphp

    <div class="row g-4">
        <div class="col-lg-8">
            <article class="soft-card overflow-hidden">
                <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="room-hero-image">
                <div class="p-4 p-lg-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <span class="room-type-chip">Room details</span>
                            <h1 class="mb-1 mt-2">{{ $room->name }}</h1>
                            <p class="hotel-meta mb-0">
                                {{ $room->type }}
                                @if(filled($room->view_type))
                                    &middot; {{ $room->view_type }}
                                @endif
                                &middot; Standard occupancy: {{ $standardGuests }} guests
                            </p>
                        </div>
                        <span class="badge-status {{ $room->is_available ? 'available' : 'unavailable' }}">
                            {{ $room->is_available ? 'Available now' : 'Currently unavailable' }}
                        </span>
                    </div>

                    <p class="text-secondary mb-4">
                        {{ $room->description ?: 'A comfortable room for a relaxing stay.' }}
                    </p>

                    <div class="room-feature-grid">
                        <div class="room-feature"><i class="bi bi-person-check me-1"></i> {{ $standardGuests }} guests</div>
                        <div class="room-feature"><i class="bi bi-grid-1x2 me-1"></i> {{ $room->type }}</div>
                        <div class="room-feature"><i class="bi bi-tree me-1"></i> {{ $room->view_type ?: 'View not specified' }}</div>
                        @foreach($room->amenities as $amenity)
                            <div class="room-feature"><i class="bi {{ $amenity['icon'] }} me-1" aria-hidden="true"></i> {{ $amenity['label'] }}</div>
                        @endforeach
                    </div>
                </div>
            </article>
        </div>

        <div class="col-lg-4">
            <aside class="room-booking-panel p-4">
                <p class="ta-eyebrow mb-1">Start Reservation</p>
                <div class="price-tag mb-1" id="room_headline_rate">
                    &#8369;{{ \App\Support\Money::display($showDetailedPricing ? ($pricingPreview['average_nightly_rate'] ?? $room->price_per_night) : $room->price_per_night) }}
                </div>
                <small class="text-secondary d-block" id="room_price_caption">
                    @if($showDetailedPricing)
                        {{ $pricingPreview ? 'average per night' : 'per night' }}
                    @else
                        per night &middot; full stay total shown after sign-in
                    @endif
                </small>
                @if($showDetailedPricing)
                <p class="small mb-3 {{ $pricingPreview && $pricingPreview['has_date_discount'] ? '' : 'd-none' }}" id="room_base_rate_wrap">
                    <span class="text-secondary text-decoration-line-through" id="room_base_rate">
                        &#8369;{{ \App\Support\Money::display($room->price_per_night) }}
                    </span>
                    <span class="text-success ms-2" id="room_discount_note">
                        @if($pricingPreview && $pricingPreview['has_date_discount'])
                            Date discount on {{ $pricingPreview['discounted_nights'] }} night{{ $pricingPreview['discounted_nights'] === 1 ? '' : 's' }}
                            &middot; Save &#8369;{{ \App\Support\Money::display($pricingPreview['discount_amount']) }}
                        @endif
                    </span>
                </p>

                <ul class="list-unstyled small text-secondary mb-4">
                    <li class="mb-2">
                        Stay:
                        <strong class="text-dark" id="room_stay_value">
                            @if($pricingPreview)
                                {{ \Carbon\Carbon::parse($pricingPreview['check_in'])->format('M d, Y') }}
                                -
                                {{ \Carbon\Carbon::parse($pricingPreview['check_out'])->format('M d, Y') }}
                                ({{ $pricingPreview['nights'] }} night{{ $pricingPreview['nights'] === 1 ? '' : 's' }})
                            @else
                                Select dates below
                            @endif
                        </strong>
                    </li>
                    <li class="mb-2">Accommodation subtotal: <strong class="text-dark" id="room_chargeable_subtotal">&#8369;{{ \App\Support\Money::display($pricingPreview['chargeable_subtotal'] ?? 0) }}</strong></li>
                    <li class="mb-2">Charges excluded from this summary: <strong class="text-dark">VAT, local tax, and service charge</strong>. Full breakdown appears on your receipt.</li>
                    <li class="mb-2">
                        Total:
                        <strong class="text-dark" id="room_total_value">
                            @if($pricingPreview)
                                &#8369;{{ \App\Support\Money::display($pricingPreview['total']) }}
                            @else
                                Select dates to preview
                            @endif
                        </strong>
                    </li>
                    <li>
                        Status:
                        <strong class="text-dark" id="room_availability_status">
                            @if($pricingPreview)
                                {{ $stayAvailability ? 'Available for selected dates' : 'Unavailable for selected dates' }}
                            @else
                                {{ $room->is_available ? 'Available' : 'Unavailable' }}
                            @endif
                        </strong>
                    </li>
                </ul>
                @else
                    <div class="room-pricing-locked" role="note">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <div>
                            <strong class="d-block text-dark">Price breakdown available after sign-in</strong>
                            <span class="small">Choose your dates to check availability, then sign in to see the subtotal, taxes, discounts, and final total.</span>
                        </div>
                    </div>
                    <p class="small text-secondary mb-3">
                        Status: <strong class="text-dark" id="room_availability_status">{{ $room->is_available ? 'Available' : 'Unavailable' }}</strong>
                    </p>
                @endif

                @if($room->is_available)
                    @if($canStartCustomerBooking)
                        <form
                            method="GET"
                            action="{{ route('bookings.create', $room) }}"
                            class="d-grid gap-2"
                            id="room_quick_booking_form"
                            data-preview-url="{{ route('rooms.pricing-preview', $room) }}"
                            data-show-detailed-pricing="{{ $showDetailedPricing ? '1' : '0' }}"
                            data-unavailable-ranges='@json($unavailableDateRanges ?? [])'
                        >
                            <div>
                                <label class="form-label small mb-1" for="room_check_in_trigger">Check-in</label>
                                <input type="hidden" name="check_in" id="room_check_in_input" value="{{ $checkIn }}">
                                <button type="button" class="room-date-trigger {{ $pricingPreview && !$stayAvailability ? 'is-unavailable' : '' }}" id="room_check_in_trigger" data-date-field="check_in" aria-expanded="false" aria-controls="room_availability_calendar">
                                    <span id="room_check_in_label">{{ \Carbon\Carbon::parse($checkIn)->format('M d, Y') }}</span>
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div>
                                <label class="form-label small mb-1" for="room_check_out_trigger">Check-out</label>
                                <input type="hidden" name="check_out" id="room_check_out_input" value="{{ $checkOut }}">
                                <button type="button" class="room-date-trigger {{ $pricingPreview && !$stayAvailability ? 'is-unavailable' : '' }}" id="room_check_out_trigger" data-date-field="check_out" aria-expanded="false" aria-controls="room_availability_calendar">
                                    <span id="room_check_out_label">{{ \Carbon\Carbon::parse($checkOut)->format('M d, Y') }}</span>
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="room-availability-calendar" id="room_availability_calendar" hidden>
                                <div class="room-calendar-header">
                                    <button type="button" class="room-calendar-nav" id="room_calendar_previous" aria-label="Previous month"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                                    <strong id="room_calendar_month"></strong>
                                    <button type="button" class="room-calendar-nav" id="room_calendar_next" aria-label="Next month"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                                </div>
                                <div class="room-calendar-weekdays" aria-hidden="true">
                                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                                        <span>{{ $weekday }}</span>
                                    @endforeach
                                </div>
                                <div class="room-calendar-days" id="room_calendar_days" role="grid" aria-label="Room availability dates"></div>
                                <div class="room-calendar-legend">
                                    <span><i class="room-calendar-legend-mark unavailable"></i>Red dates are unavailable</span>
                                </div>
                            </div>
                            <p class="small text-secondary mb-1">
                                {{ $standardGuests }} guests included. Extra bed available.
                            </p>
                            @if(!$viewer)
                                <p class="small text-secondary mb-1">
                                    <i class="bi bi-shield-lock me-1"></i>
                                    Sign in is required to book.
                                </p>
                            @endif
                            <button
                                type="submit"
                                class="btn btn-ta w-100"
                                id="room_booking_submit"
                                data-ready-label="{{ $bookingButtonLabel }}"
                            >{{ $bookingButtonLabel }}</button>
                        </form>
                        <div class="small mt-2 text-secondary" id="room_booking_feedback" aria-live="polite"></div>
                    @else
                        <div class="alert alert-light border small mb-2">
                            Customer bookings require a customer account.
                        </div>
                        <a
                            href="{{ $viewer->isAdmin() ? route('admin.dashboard') : route('staff.dashboard') }}"
                            class="btn btn-ta w-100"
                        >Return to dashboard</a>
                    @endif
                @else
                    <button class="btn btn-secondary w-100" disabled>Unavailable for booking</button>
                @endif

                <x-back-button :href="route('rooms.index')" label="Back to rooms" class="w-100 mt-2" />
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('room_quick_booking_form');
            const checkInInput = document.getElementById('room_check_in_input');
            const checkOutInput = document.getElementById('room_check_out_input');
            const checkInTrigger = document.getElementById('room_check_in_trigger');
            const checkOutTrigger = document.getElementById('room_check_out_trigger');
            const checkInLabel = document.getElementById('room_check_in_label');
            const checkOutLabel = document.getElementById('room_check_out_label');
            const availabilityCalendar = document.getElementById('room_availability_calendar');
            const calendarMonthLabel = document.getElementById('room_calendar_month');
            const calendarDays = document.getElementById('room_calendar_days');
            const calendarPrevious = document.getElementById('room_calendar_previous');
            const calendarNext = document.getElementById('room_calendar_next');
            const headlineRate = document.getElementById('room_headline_rate');
            const priceCaption = document.getElementById('room_price_caption');
            const baseRateWrap = document.getElementById('room_base_rate_wrap');
            const baseRate = document.getElementById('room_base_rate');
            const discountNote = document.getElementById('room_discount_note');
            const stayValue = document.getElementById('room_stay_value');
            const totalValue = document.getElementById('room_total_value');
            const chargeableSubtotalValue = document.getElementById('room_chargeable_subtotal');
            const availabilityStatus = document.getElementById('room_availability_status');
            const bookingFeedback = document.getElementById('room_booking_feedback');
            const bookingSubmit = document.getElementById('room_booking_submit');
            const baseNightlyRate = Number.parseFloat('{{ number_format((float) $room->price_per_night, 2, '.', '') }}') || 0;
            const showDetailedPricing = form?.dataset.showDetailedPricing === '1';
            let unavailableRanges = [];
            let activeDateField = null;
            let displayedMonth = null;

            if (!form || !checkInInput || !checkOutInput) {
                return;
            }

            try {
                unavailableRanges = JSON.parse(form.dataset.unavailableRanges || '[]');
            } catch (_) {
                unavailableRanges = [];
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const dateFormatter = new Intl.DateTimeFormat('en-PH', {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
            });
            const monthFormatter = new Intl.DateTimeFormat('en-PH', {
                month: 'long',
                year: 'numeric',
            });
            const currencyFormatter = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            });

            const formatDate = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            const parseInputDate = (value) => {
                if (!value) {
                    return null;
                }
                const parsed = new Date(`${value}T00:00:00`);
                return Number.isNaN(parsed.getTime()) ? null : parsed;
            };

            const isSameDate = (first, second) => first && second && formatDate(first) === formatDate(second);

            const isUnavailableDate = (date) => unavailableRanges.some((range) => {
                const rangeStart = parseInputDate(range.check_in);
                const rangeEnd = parseInputDate(range.check_out);
                return rangeStart && rangeEnd && date >= rangeStart && date < rangeEnd;
            });

            const isAvailableRange = (start, end) => {
                if (!start || !end || end <= start) {
                    return false;
                }

                const cursor = new Date(start);
                while (cursor < end) {
                    if (isUnavailableDate(cursor)) {
                        return false;
                    }
                    cursor.setDate(cursor.getDate() + 1);
                }

                return true;
            };

            const syncDateLabels = () => {
                const checkIn = parseInputDate(checkInInput.value);
                const checkOut = parseInputDate(checkOutInput.value);
                if (checkInLabel) checkInLabel.textContent = checkIn ? dateFormatter.format(checkIn) : 'Select check-in';
                if (checkOutLabel) checkOutLabel.textContent = checkOut ? dateFormatter.format(checkOut) : 'Select check-out';
            };

            const closeCalendar = () => {
                if (availabilityCalendar) availabilityCalendar.hidden = true;
                checkInTrigger?.setAttribute('aria-expanded', 'false');
                checkOutTrigger?.setAttribute('aria-expanded', 'false');
                activeDateField = null;
            };

            const renderCalendar = () => {
                if (!calendarDays || !calendarMonthLabel || !displayedMonth || !activeDateField) {
                    return;
                }

                const year = displayedMonth.getFullYear();
                const month = displayedMonth.getMonth();
                const firstWeekday = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const selectedCheckIn = parseInputDate(checkInInput.value);
                const selectedCheckOut = parseInputDate(checkOutInput.value);

                calendarMonthLabel.textContent = monthFormatter.format(displayedMonth);
                calendarDays.replaceChildren();

                for (let blank = 0; blank < firstWeekday; blank += 1) {
                    calendarDays.append(document.createElement('span'));
                }

                for (let day = 1; day <= daysInMonth; day += 1) {
                    const date = new Date(year, month, day);
                    const unavailable = isUnavailableDate(date);
                    const isPast = date < today;
                    const isCheckOutChoice = activeDateField === 'check_out';
                    const selectable = isCheckOutChoice
                        ? !isPast && selectedCheckIn && date > selectedCheckIn && isAvailableRange(selectedCheckIn, date)
                        : !isPast && !unavailable;
                    const button = document.createElement('button');

                    button.type = 'button';
                    button.className = 'room-calendar-day';
                    button.textContent = String(day);
                    button.dataset.date = formatDate(date);
                    button.setAttribute('role', 'gridcell');
                    button.setAttribute('aria-label', `${dateFormatter.format(date)}${unavailable ? ', unavailable' : ', available'}`);
                    button.title = unavailable
                        ? (selectable ? 'Unavailable for an overnight stay; allowed as your check-out boundary.' : 'Unavailable')
                        : 'Available';

                    if (unavailable) button.classList.add('is-unavailable');
                    if (isSameDate(date, selectedCheckIn) || isSameDate(date, selectedCheckOut)) {
                        button.classList.add('is-selected');
                    }
                    button.disabled = !selectable;

                    button.addEventListener('click', () => {
                        if (activeDateField === 'check_in') {
                            checkInInput.value = button.dataset.date;
                            applyDateRules();
                            checkInInput.dispatchEvent(new Event('change'));
                            activeDateField = 'check_out';
                            checkInTrigger?.setAttribute('aria-expanded', 'false');
                            checkOutTrigger?.setAttribute('aria-expanded', 'true');
                            displayedMonth = new Date(parseInputDate(checkOutInput.value) || date);
                            displayedMonth.setDate(1);
                            renderCalendar();
                            return;
                        }

                        checkOutInput.value = button.dataset.date;
                        syncDateLabels();
                        checkOutInput.dispatchEvent(new Event('change'));
                        closeCalendar();
                    });

                    calendarDays.append(button);
                }

                const previousMonth = new Date(year, month - 1, 1);
                calendarPrevious.disabled = previousMonth < new Date(today.getFullYear(), today.getMonth(), 1);
            };

            const openCalendar = (field) => {
                if (!availabilityCalendar) return;
                activeDateField = field;
                const selectedDate = parseInputDate(field === 'check_in' ? checkInInput.value : checkOutInput.value) || today;
                displayedMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
                availabilityCalendar.hidden = false;
                checkInTrigger?.setAttribute('aria-expanded', field === 'check_in' ? 'true' : 'false');
                checkOutTrigger?.setAttribute('aria-expanded', field === 'check_out' ? 'true' : 'false');
                renderCalendar();
            };

            const formatCurrency = (value) => currencyFormatter.format(Math.max(0, Number(value) || 0));

            const applyDateRules = () => {
                const selectedCheckIn = parseInputDate(checkInInput.value) ?? today;
                const checkInBase = selectedCheckIn < today ? today : selectedCheckIn;
                const minCheckoutDate = new Date(checkInBase);
                minCheckoutDate.setDate(minCheckoutDate.getDate() + 1);
                const minCheckOut = formatDate(minCheckoutDate);
                checkOutInput.min = minCheckOut;

                if (!checkOutInput.value || checkOutInput.value < minCheckOut) {
                    checkOutInput.value = minCheckOut;
                }

                syncDateLabels();
            };

            const setAvailabilityState = (availability, fallbackMessage = 'Select valid dates to preview.') => {
                if (availabilityStatus) {
                    availabilityStatus.textContent = availability?.message || fallbackMessage;
                }

                if (bookingFeedback) {
                    bookingFeedback.textContent = availability?.message || fallbackMessage;
                }

                if (bookingSubmit) {
                    const canContinue = availability?.stay_available ?? false;
                    bookingSubmit.disabled = !canContinue;
                    bookingSubmit.textContent = canContinue
                        ? bookingSubmit.dataset.readyLabel
                        : 'Unavailable for selected dates';
                }

                const unavailableSelection = availability?.stay_available === false;
                checkInTrigger?.classList.toggle('is-unavailable', unavailableSelection);
                checkOutTrigger?.classList.toggle('is-unavailable', unavailableSelection);
            };

            const setFallbackPricing = (message = 'Select valid dates to preview.') => {
                if (showDetailedPricing && headlineRate) {
                    headlineRate.textContent = formatCurrency(baseNightlyRate);
                }

                if (showDetailedPricing && priceCaption) {
                    priceCaption.textContent = 'per night';
                }

                if (baseRateWrap) {
                    baseRateWrap.classList.add('d-none');
                }

                if (stayValue) {
                    stayValue.textContent = 'Select dates below';
                }

                if (totalValue) {
                    totalValue.textContent = 'Select dates to preview';
                }
                [chargeableSubtotalValue].forEach((value) => {
                    if (value) value.textContent = '--';
                });

                setAvailabilityState(null, message);
            };

            const setPricingPreview = (pricing, availability) => {
                if (showDetailedPricing && headlineRate) {
                    headlineRate.textContent = formatCurrency(pricing.average_nightly_rate);
                }

                if (showDetailedPricing && priceCaption) {
                    priceCaption.textContent = 'average per night';
                }

                if (baseRate) {
                    baseRate.textContent = formatCurrency(pricing.base_nightly_rate);
                }

                if (baseRateWrap) {
                    baseRateWrap.classList.toggle('d-none', !pricing.has_date_discount);
                }

                if (discountNote) {
                    discountNote.textContent = pricing.has_date_discount
                        ? `Date discount on ${pricing.discounted_nights} night${pricing.discounted_nights === 1 ? '' : 's'} · Save ${formatCurrency(pricing.discount_amount)}`
                        : '';
                }

                if (stayValue) {
                    const stayStart = parseInputDate(pricing.check_in);
                    const stayEnd = parseInputDate(pricing.check_out);
                    stayValue.textContent = stayStart && stayEnd
                        ? `${dateFormatter.format(stayStart)} - ${dateFormatter.format(stayEnd)} (${pricing.nights} night${pricing.nights === 1 ? '' : 's'})`
                        : 'Select dates below';
                }

                if (totalValue) {
                    totalValue.textContent = formatCurrency(pricing.total);
                }
                if (chargeableSubtotalValue) chargeableSubtotalValue.textContent = formatCurrency(pricing.chargeable_subtotal);

                setAvailabilityState(availability, 'Select valid dates to preview.');
            };

            const refreshPricingPreview = async () => {
                const checkIn = parseInputDate(checkInInput.value);
                const checkOut = parseInputDate(checkOutInput.value);

                if (!checkIn || !checkOut || checkOut <= checkIn) {
                    setFallbackPricing('Select a valid check-in and check-out date range.');
                    return;
                }

                if (bookingSubmit) {
                    bookingSubmit.disabled = true;
                    bookingSubmit.textContent = 'Checking availability...';
                }

                if (bookingFeedback) {
                    bookingFeedback.textContent = 'Checking live price and availability...';
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
                        setFallbackPricing(payload?.message || 'Unable to load live room pricing right now.');
                        return;
                    }

                    setPricingPreview(payload.pricing, payload.availability);
                } catch (error) {
                    setFallbackPricing('Unable to load live room pricing right now.');
                }
            };

            checkInTrigger?.addEventListener('click', () => {
                if (!availabilityCalendar?.hidden && activeDateField === 'check_in') {
                    closeCalendar();
                    return;
                }
                openCalendar('check_in');
            });

            checkOutTrigger?.addEventListener('click', () => {
                if (!availabilityCalendar?.hidden && activeDateField === 'check_out') {
                    closeCalendar();
                    return;
                }
                openCalendar('check_out');
            });

            calendarPrevious?.addEventListener('click', () => {
                if (!displayedMonth) return;
                displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() - 1, 1);
                renderCalendar();
            });

            calendarNext?.addEventListener('click', () => {
                if (!displayedMonth) return;
                displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 1);
                renderCalendar();
            });

            document.addEventListener('click', (event) => {
                if (availabilityCalendar?.hidden) return;
                if (availabilityCalendar?.contains(event.target) || checkInTrigger?.contains(event.target) || checkOutTrigger?.contains(event.target)) return;
                closeCalendar();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeCalendar();
            });

            checkInInput.addEventListener('change', () => {
                applyDateRules();
                refreshPricingPreview();
            });
            checkOutInput.addEventListener('change', () => {
                applyDateRules();
                refreshPricingPreview();
            });

            applyDateRules();
            refreshPricingPreview();
        })();
    </script>
@endpush
