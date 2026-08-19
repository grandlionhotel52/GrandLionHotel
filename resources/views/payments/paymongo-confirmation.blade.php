@extends('layouts.app')

@section('title', 'PayMongo Payment Confirmation')

@section('content')
    @php($isPaid = $booking->payment_status === 'paid')
    <div class="row justify-content-center py-4">
        <div class="col-lg-7">
            <section class="soft-card p-4 p-lg-5 text-center" id="payment-confirmation" data-status-url="{{ route('payments.paymongo.status', $booking) }}" data-is-paid="{{ $isPaid ? '1' : '0' }}">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle {{ $isPaid ? 'bg-success' : 'bg-warning' }} text-white fs-2 mb-3" style="width:4rem;height:4rem" aria-hidden="true">
                    <i class="bi {{ $isPaid ? 'bi-check2' : 'bi-hourglass-split' }}"></i>
                </span>

                @if($isPaid)
                    <p class="ta-eyebrow mb-1">Payment confirmed</p>
                    <h1 class="h2 mb-3">Your payment was successful</h1>
                    <p class="text-secondary">No proof upload is needed. PayMongo securely confirmed your payment.</p>
                @else
                    <p class="ta-eyebrow mb-1">Payment submitted</p>
                    <h1 class="h2 mb-3">We are confirming your payment</h1>
                    <p class="text-secondary">Please do not pay again or upload proof. This page will update automatically when PayMongo confirms the transaction.</p>
                    <div class="spinner-border text-warning my-2" role="status"><span class="visually-hidden">Checking payment status</span></div>
                @endif

                <div class="border rounded-3 p-3 my-4 text-start">
                    <div class="d-flex justify-content-between gap-3 mb-2"><span class="text-secondary">Booking</span><strong>#{{ $booking->id }}</strong></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span class="text-secondary">Room</span><strong>{{ $booking->room?->name ?? 'N/A' }}</strong></div>
                    <div class="d-flex justify-content-between gap-3 mb-2"><span class="text-secondary">Amount</span><strong>₱{{ number_format((float) ($booking->payment?->amount ?? $booking->total_price), 2) }}</strong></div>
                    <div class="d-flex justify-content-between gap-3"><span class="text-secondary">Status</span><strong class="{{ $isPaid ? 'text-success' : 'text-warning' }}">{{ $isPaid ? 'Paid' : 'Confirming' }}</strong></div>
                </div>

                @if($isPaid)
                    <div class="alert alert-success text-start">
                        <strong>Payment proof</strong><br>
                        Transaction reference: {{ $booking->payment?->transaction_reference ?? 'Recorded' }}
                        @if($booking->payment?->provider_payment_id)<br>PayMongo ID: {{ $booking->payment->provider_payment_id }}@endif
                    </div>
                @else
                    <p class="small text-secondary" id="confirmation-help">Confirmation normally takes a few seconds. You may safely leave this page and check My Bookings later.</p>
                @endif

                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                    <a href="{{ route('bookings.show', $booking) }}" class="btn btn-ta">View booking</a>
                    @if($isPaid)
                        <a href="{{ route('bookings.receipt', $booking) }}" class="btn btn-ta-outline">Download receipt</a>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const card = document.getElementById('payment-confirmation');
            if (!card || card.dataset.isPaid === '1') return;

            let attempts = 0;
            const checkStatus = async () => {
                attempts += 1;
                try {
                    const response = await fetch(card.dataset.statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                    const data = await response.json();
                    if (response.ok && data.paid) {
                        window.GrandLionAjaxNavigation?.visit(window.location.href) ?? window.location.reload();
                        return;
                    }
                } catch (_) {}

                if (attempts < 30) {
                    window.setTimeout(checkStatus, 2000);
                } else {
                    const help = document.getElementById('confirmation-help');
                    if (help) help.textContent = 'Confirmation is taking longer than expected. Do not pay again. Check My Bookings shortly or contact the hotel with your PayMongo receipt.';
                }
            };

            window.setTimeout(checkStatus, 1500);
        })();
    </script>
@endpush
