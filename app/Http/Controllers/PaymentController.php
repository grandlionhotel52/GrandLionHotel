<?php

namespace App\Http\Controllers;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PayMongoCheckoutSession;
use App\Services\PaymentService;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PayMongoService $payMongoService,
    )
    {
    }

    public function checkout(Booking $booking)
    {
        $this->authorizeOwner($booking);
        $booking->loadMissing(['room', 'guestDetail']);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.success', $booking)
                ->with('status', 'This booking is already paid.');
        }

        if ($booking->payment_status === 'pending_verification') {
            return redirect()->route('bookings.show', $booking)
                ->with('status', 'Your online payment proof is under review. Please wait for staff verification.');
        }

        if ($booking->status === 'pending') {
            return redirect()->route('bookings.show', $booking)->withErrors([
                'booking' => 'Your booking is still pending. Please wait for staff confirmation before payment.',
            ]);
        }

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return redirect()->route('bookings.my')->withErrors([
                'method' => 'This booking cannot be paid in its current status.',
            ]);
        }

        return view('payments.checkout', compact('booking'));
    }

    public function process(Request $request, Booking $booking)
    {
        $this->authorizeOwner($booking);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.success', $booking)
                ->with('status', 'This booking is already paid.');
        }

        if ($booking->payment_status === 'pending_verification') {
            return back()->withErrors([
                'method' => 'Your payment proof is already submitted and is waiting for staff verification.',
            ]);
        }

        if ($booking->status === 'pending') {
            return back()->withErrors([
                'method' => 'Payment is disabled until staff confirms your booking.',
            ]);
        }

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors([
                'method' => 'This booking cannot be paid in its current status.',
            ]);
        }

        $validated = $request->validate([
            'method' => ['required', Rule::in(Payment::allowedMethods())],
            'qr_reference' => ['nullable', 'string', 'max:80'],
            'customer_reference' => ['nullable', 'string', 'max:120', Rule::requiredIf(fn (): bool => $request->input('method') === Payment::METHOD_INSTAPAY)],
            'payment_proof' => ['nullable', 'image', 'max:5120', Rule::requiredIf(fn (): bool => $request->input('method') === Payment::METHOD_INSTAPAY)],
            'terms_accepted' => ['exclude_unless:method,'.Payment::METHOD_INSTAPAY, 'required', 'accepted'],
        ]);

        if ($validated['method'] === Payment::METHOD_CREDIT_DEBIT_CARD) {
            $amountCentavos = (int) round((float) $booking->total_price * 100);
            $existingSession = PayMongoCheckoutSession::query()
                ->where('booking_id', $booking->id)
                ->where('amount_centavos', $amountCentavos)
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subMinutes(30))
                ->latest('paymongo_checkout_session_id')
                ->first();

            if ($existingSession) {
                return redirect()->away($existingSession->checkout_url);
            }

            try {
                $session = $this->payMongoService->createCardCheckout($booking);
            } catch (Throwable $exception) {
                report($exception);

                return back()->withInput()->withErrors([
                    'method' => $exception->getMessage(),
                ]);
            }

            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->total_price,
                    'method' => Payment::METHOD_CREDIT_DEBIT_CARD,
                    'status' => 'unpaid',
                    'source' => 'paymongo_checkout_pending',
                    'provider_session_id' => $session['id'],
                    'provider_payment_id' => null,
                    'qr_reference' => null,
                    'customer_reference' => null,
                    'payment_proof_path' => null,
                    'transaction_reference' => null,
                    'paid_at' => null,
                    'verified_at' => null,
                ]
            );

            PayMongoCheckoutSession::query()->create([
                'booking_id' => $booking->id,
                'provider_session_id' => $session['id'],
                'checkout_url' => $session['checkout_url'],
                'amount_centavos' => $amountCentavos,
                'status' => 'pending',
            ]);

            return redirect()->away($session['checkout_url']);
        }

        if ($validated['method'] === Payment::METHOD_CASH) {
            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->total_price,
                    'method' => Payment::METHOD_CASH,
                    'status' => 'unpaid',
                    'source' => 'cash_pending',
                    'provider_session_id' => null,
                    'provider_payment_id' => null,
                    'qr_reference' => null,
                    'customer_reference' => null,
                    'payment_proof_path' => null,
                    'transaction_reference' => null,
                    'paid_at' => null,
                    'verified_at' => null,
                ]
            );

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Cash payment selected. Please pay at the front desk. Staff will confirm and mark this booking as paid once payment is received.');
        }

        if ($validated['method'] === Payment::METHOD_INSTAPAY) {
            $existingPayment = $booking->payment()->first();
            $oldProofPath = trim((string) ($existingPayment?->payment_proof_path ?? ''));
            $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public');

            if ($oldProofPath !== '' && $oldProofPath !== $proofPath) {
                Storage::disk('public')->delete($oldProofPath);
            }

            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->total_price,
                    'method' => $validated['method'],
                    'status' => 'pending_verification',
                    'source' => 'online_submitted',
                    'qr_reference' => $validated['qr_reference'] ?? null,
                    'customer_reference' => $validated['customer_reference'] ?? null,
                    'payment_proof_path' => $proofPath,
                    'paid_at' => null,
                    'verified_at' => null,
                    'transaction_reference' => null,
                    'original_amount' => null,
                    'discount_rate' => null,
                    'discount_amount' => null,
                ]
            );

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Payment proof submitted. Staff will review your online payment before marking it as paid.');
        }

        $this->paymentService->charge($booking, $validated['method'], [
            'qr_reference' => $validated['qr_reference'] ?? null,
        ]);
        $booking->refresh()->loadMissing(['user', 'room', 'payment']);

        try {
            Mail::to($booking->user->email)->queue(new BookingPaidMail($booking));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('bookings.success', $booking);
    }

    public function payMongoReturn(Booking $booking)
    {
        $this->authorizeOwner($booking);
        $booking->refresh()->loadMissing('payment');

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.success', $booking)
                ->with('status', 'Your PayMongo card payment was successful.');
        }

        return redirect()->route('bookings.show', $booking)
            ->with('status', 'PayMongo is confirming your card payment. This page will show paid after the secure notification arrives.');
    }

    private function authorizeOwner(Booking $booking): void
    {
        if (auth()->id() !== $booking->customer_id) {
            abort(403);
        }
    }
}
