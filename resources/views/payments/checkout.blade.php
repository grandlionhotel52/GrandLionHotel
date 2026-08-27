@extends('layouts.app')

@section('title', 'Checkout')

@push('head')
    <style>
        .checkout-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #fffdf9;
            padding: 1rem;
        }
        .checkout-summary-item {
            border-radius: 12px;
            background: #fff;
            padding: 0.7rem 0.8rem;
        }
        .checkout-summary-item.total {
            grid-column: 1 / -1;
            border: 1px solid rgba(184, 146, 84, 0.38);
            background: rgba(184, 146, 84, 0.09);
        }
        .checkout-summary-label {
            display: block;
            color: var(--muted);
            font-size: 0.75rem;
            font-weight: 700;
            margin-bottom: 0.15rem;
        }
        .checkout-summary-value {
            font-weight: 800;
            color: var(--ink);
        }
        .instapay-panel {
            border: 1px solid rgba(23, 92, 211, 0.25);
            border-radius: 16px;
            background: rgba(23, 92, 211, 0.05);
            padding: 1rem;
        }
        .instapay-qr {
            width: min(220px, 100%);
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            padding: 0.45rem;
        }
        @media (max-width: 575.98px) {
            .checkout-summary {
                grid-template-columns: 1fr;
            }
            .checkout-summary-item.total {
                grid-column: auto;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $preferredMethod = data_get($booking->reservation_meta, 'payment_preference');
        $legacyMethodMap = [
            'bank_transfer' => 'credit_debit_card',
            'gcash' => 'credit_debit_card',
            'paymaya' => 'credit_debit_card',
            'instapay' => 'credit_debit_card',
        ];
        $selectedMethod = old('method', $preferredMethod);
        $selectedMethod = $legacyMethodMap[$selectedMethod] ?? $selectedMethod;
        $onlineMethods = ['instapay'];
        $pricingQuote = $booking->pricingQuote();
        $billedUnits = max(1, $booking->nights());
        $subtotalAmount = (float) ($booking->payment?->amount ?? $booking->total_price);
        $roomSubtotal = (float) ($pricingQuote['room_total'] ?? $subtotalAmount);
        $roomNightlyRate = (float) ($pricingQuote['base_nightly_rate'] ?? 0);
        $extraBeddingCount = (int) ($pricingQuote['extra_bedding_count'] ?? 0);
        $extraBeddingFeePerNight = (float) ($pricingQuote['extra_bedding_fee_per_night'] ?? 0);
        $extraBeddingTotal = (float) ($pricingQuote['extra_bedding_total'] ?? 0);
        $chargeableSubtotal = (float) ($pricingQuote['chargeable_subtotal'] ?? ($roomSubtotal + $extraBeddingTotal));
        $serviceFee = (float) ($pricingQuote['service_fee'] ?? 0);
        $localTax = (float) ($pricingQuote['local_tax'] ?? 0);
        $vat = (float) ($pricingQuote['vat'] ?? 0);
        $discountAmount = (float) ($booking->payment?->discount_amount ?? 0);
        $merchantName = (string) config('services.qr_wallets.merchant_name', config('app.name'));
        $configuredQrUrl = trim((string) data_get(config('services.qr_wallets'), 'instapay.qr_image_url', ''));
        $instapayQrUrl = $configuredQrUrl;

        if ($configuredQrUrl !== '' && preg_match('/^https?:\/\//i', $configuredQrUrl) !== 1 && !str_starts_with($configuredQrUrl, '/')) {
            $normalizedQrUrl = str_replace('\\', '/', $configuredQrUrl);
            $publicPrefix = rtrim(str_replace('\\', '/', public_path()), '/').'/';
            $instapayQrUrl = str_starts_with(strtolower($normalizedQrUrl), strtolower($publicPrefix))
                ? '/'.ltrim(substr($normalizedQrUrl, strlen($publicPrefix)), '/')
                : '/'.ltrim($normalizedQrUrl, '/');
        }

        $instapayHolder = (string) data_get(config('services.qr_wallets'), 'instapay.holder_name', $merchantName);
        $instapayNumber = (string) data_get(config('services.qr_wallets'), 'instapay.number', '');
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <section class="soft-card p-4 p-lg-5">
                <p class="ta-eyebrow mb-1">Secure Payment</p>
                <h1 class="h3 mb-1">Complete payment</h1>
                <p class="text-secondary mb-3">Booking #{{ $booking->id }} &middot; Review the amount before submitting.</p>

                <div class="alert alert-light border small mb-4" role="note">
                    <strong>Choose how you want to pay.</strong> Online payment is confirmed automatically after PayMongo succeeds. For cash, pay at the front desk and staff will record it after receiving your payment.
                </div>

                <div class="checkout-summary mb-4">
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">Room</span>
                        <span class="checkout-summary-value">{{ $booking->room->name ?? 'N/A' }}</span>
                    </div>
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">Stay</span>
                        <span class="checkout-summary-value">{{ $booking->check_in->format('M d') }} &ndash; {{ $booking->check_out->format('M d, Y') }}</span>
                    </div>
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">Nightly rate</span>
                        <span class="checkout-summary-value">&#8369;{{ number_format($roomNightlyRate, 2) }} &times; {{ $billedUnits }}</span>
                    </div>
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">Room subtotal</span>
                        <span class="checkout-summary-value">&#8369;{{ number_format($roomSubtotal, 2) }}</span>
                    </div>
                    @if($extraBeddingCount > 0)
                        <div class="checkout-summary-item">
                            <span class="checkout-summary-label">Extra bedding</span>
                            <span class="checkout-summary-value">{{ $extraBeddingCount }} &times; &#8369;{{ number_format($extraBeddingFeePerNight, 2) }} &times; {{ $billedUnits }}</span>
                        </div>
                    @endif
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">Accommodation subtotal</span>
                        <span class="checkout-summary-value">&#8369;{{ number_format($chargeableSubtotal, 2) }}</span>
                    </div>
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">Service charge (8%, with breakfast only)</span>
                        <span class="checkout-summary-value">&#8369;{{ number_format($serviceFee, 2) }}</span>
                    </div>
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">Local tax (5%)</span>
                        <span class="checkout-summary-value">&#8369;{{ number_format($localTax, 2) }}</span>
                    </div>
                    <div class="checkout-summary-item">
                        <span class="checkout-summary-label">VAT (12%, exclusive)</span>
                        <span class="checkout-summary-value">&#8369;{{ number_format($vat, 2) }}</span>
                    </div>
                    @if($discountAmount > 0)
                        <div class="checkout-summary-item text-success">
                            <span class="checkout-summary-label">Discount</span>
                            <span class="checkout-summary-value">-&#8369;{{ number_format($discountAmount, 2) }}</span>
                        </div>
                    @endif
                    <div class="checkout-summary-item total">
                        <span class="checkout-summary-label">Amount due</span>
                        <span class="checkout-summary-value fs-4">&#8369;{{ number_format($subtotalAmount, 2) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('payments.process', $booking) }}" class="row g-3" id="payment_checkout_form" enctype="multipart/form-data">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Payment method</label>
                        <select class="form-select" name="method" id="payment_method_select" required>
                            <option value="cash" @selected($selectedMethod === 'cash')>Cash &mdash; Pay at the front desk</option>
                            <option value="credit_debit_card" @selected($selectedMethod === 'credit_debit_card')>Pay online &mdash; Card, GCash, Maya, or QR Ph</option>
                        </select>
                        <small class="text-secondary">
                            Online payments open PayMongo's secure checkout. Cash is paid at the front desk.
                        </small>
                    </div>

                    <div class="col-12 {{ $selectedMethod === 'credit_debit_card' ? '' : 'd-none' }}" id="card_terminal_panel">
                        <div class="alert alert-info mb-0">
                            <div class="d-flex gap-2 align-items-start">
                                <i class="bi bi-credit-card fs-5" aria-hidden="true"></i>
                                <div>
                                    <strong class="d-block mb-1">Card, GCash, Maya, and QR Ph powered by PayMongo</strong>
                                    <span class="small">You will be redirected to PayMongo to choose an available payment method. The hotel never receives or stores sensitive account or card details.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 {{ in_array($selectedMethod, $onlineMethods, true) ? '' : 'd-none' }}" id="online_verification_fields">
                        <div class="row g-3">
                            <div class="col-12 {{ $selectedMethod === 'instapay' ? '' : 'd-none' }}" id="instapay_qr_panel">
                                <div class="instapay-panel">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-auto text-center">
                                            @if($instapayQrUrl !== '')
                                                <img src="{{ $instapayQrUrl }}" alt="InstaPay QR code for {{ $instapayHolder }}" class="instapay-qr">
                                            @else
                                                <div class="alert alert-warning mb-0">InstaPay QR is not configured.</div>
                                            @endif
                                        </div>
                                        <div class="col">
                                            <p class="ta-eyebrow mb-1">InstaPay QR</p>
                                            <h2 class="h5 mb-2">Scan to pay</h2>
                                            <p class="small text-secondary mb-1">Account name: <strong class="text-dark">{{ $instapayHolder }}</strong></p>
                                            @if($instapayNumber !== '')
                                                <p class="small text-secondary mb-1">Account number: <strong class="text-dark">{{ $instapayNumber }}</strong></p>
                                            @endif
                                            <p class="small text-secondary mb-0">Amount: <strong class="text-dark">&#8369;{{ number_format($subtotalAmount, 2) }}</strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Transaction reference number</label>
                                <input
                                    type="text"
                                    class="form-control @error('customer_reference') is-invalid @enderror"
                                    name="customer_reference"
                                    id="customer_reference_input"
                                    maxlength="120"
                                    value="{{ old('customer_reference') }}"
                                    placeholder="Enter transaction reference no."
                                >
                                @error('customer_reference')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Provider reference (optional)</label>
                                <input
                                    type="text"
                                    class="form-control @error('qr_reference') is-invalid @enderror"
                                    name="qr_reference"
                                    maxlength="80"
                                    value="{{ old('qr_reference') }}"
                                    placeholder="Optional bank/network reference"
                                >
                                @error('qr_reference')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Payment proof</label>
                                <input
                                    type="file"
                                    class="form-control @error('payment_proof') is-invalid @enderror"
                                    name="payment_proof"
                                    id="payment_proof_input"
                                    accept="image/*"
                                >
                                <small class="text-secondary">Upload a clear image of the successful payment.</small>
                                @error('payment_proof')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <p class="small text-secondary mb-2">
                                    Staff will verify online payments before marking the booking as paid.
                                </p>
                                <div class="form-check">
                                    <input
                                        class="form-check-input @error('terms_accepted') is-invalid @enderror"
                                        type="checkbox"
                                        value="1"
                                        name="terms_accepted"
                                        id="terms_accepted"
                                        @checked(old('terms_accepted'))
                                    >
                                    <label class="form-check-label" for="terms_accepted">
                                        I agree to the
                                        <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms and Conditions</a>.
                                    </label>
                                    @error('terms_accepted')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <x-back-button :href="$backRoute ?? route('bookings.show', $booking)" label="Back to booking details" />
                        <button type="submit" class="btn btn-ta" id="payment_submit_button">
                            {{ in_array($selectedMethod, $onlineMethods, true) ? 'Submit for verification' : ($selectedMethod === 'credit_debit_card' ? 'Continue to secure payment' : 'Choose cash payment') }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const methodSelect = document.getElementById('payment_method_select');
            const onlineFields = document.getElementById('online_verification_fields');
            const instapayPanel = document.getElementById('instapay_qr_panel');
            const cardTerminalPanel = document.getElementById('card_terminal_panel');
            const submitButton = document.getElementById('payment_submit_button');
            const termsCheckbox = document.getElementById('terms_accepted');
            const onlineMethods = ['instapay'];

            if (!methodSelect || !onlineFields || !submitButton) {
                return;
            }

            const updateUi = () => {
                const requiresOnlineProof = onlineMethods.includes(methodSelect.value);
                onlineFields.classList.toggle('d-none', !requiresOnlineProof);
                instapayPanel?.classList.toggle('d-none', methodSelect.value !== 'instapay');
                cardTerminalPanel?.classList.toggle('d-none', methodSelect.value !== 'credit_debit_card');
                submitButton.textContent = requiresOnlineProof
                    ? 'Submit for verification'
                    : methodSelect.value === 'credit_debit_card' ? 'Continue to secure payment' : 'Choose cash payment';
                submitButton.disabled = requiresOnlineProof && !termsCheckbox?.checked;
            };

            methodSelect.addEventListener('change', updateUi);
            termsCheckbox?.addEventListener('change', updateUi);
            updateUi();
        })();
    </script>
@endpush
