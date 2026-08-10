<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RefundStatusMail;
use App\Models\Payment;
use App\Models\RefundRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $status = trim($request->string('status')->toString());
        $keyword = trim($request->string('q')->toString());

        $refunds = RefundRequest::query()
            ->with(['payment.booking.customer', 'payment.booking.guestDetail', 'handledByAdmin'])
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $nested) use ($keyword): void {
                    $nested->where('refund_request_id', $keyword)
                        ->orWhere('transaction_reference', 'like', '%'.$keyword.'%')
                        ->orWhereHas('payment.booking', fn (Builder $bookingQuery) => $bookingQuery->where('booking_id', $keyword))
                        ->orWhereHas('payment.booking.customer', fn (Builder $customerQuery) => $customerQuery
                            ->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('email', 'like', '%'.$keyword.'%'));
                });
            })
            ->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'pending' => RefundRequest::where('status', RefundRequest::STATUS_PENDING)->count(),
            'approved' => RefundRequest::where('status', RefundRequest::STATUS_APPROVED)->count(),
            'processed' => RefundRequest::where('status', RefundRequest::STATUS_PROCESSED)->count(),
            'rejected' => RefundRequest::where('status', RefundRequest::STATUS_REJECTED)->count(),
            'refunded_total' => (float) RefundRequest::where('status', RefundRequest::STATUS_PROCESSED)->sum('amount'),
        ];

        return view('admin.refunds.index', compact('refunds', 'summary', 'status', 'keyword'));
    }

    public function show(RefundRequest $refund)
    {
        $refund->load(['payment.booking.customer', 'payment.booking.room', 'payment.booking.guestDetail', 'handledByAdmin']);

        return view('admin.refunds.show', compact('refund'));
    }

    public function approve(Request $request, RefundRequest $refund)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'refund_method' => ['required', Rule::in(Payment::allowedMethods())],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $refund = DB::transaction(function () use ($refund, $validated): RefundRequest {
            $locked = RefundRequest::query()->with('payment')->lockForUpdate()->findOrFail($refund->id);
            if (! $locked->canTransitionTo(RefundRequest::STATUS_APPROVED)) {
                throw ValidationException::withMessages(['refund' => 'Only pending refund requests can be approved.']);
            }

            if ((float) $validated['amount'] > (float) $locked->payment->amount) {
                throw ValidationException::withMessages(['amount' => 'Refund amount cannot exceed the original payment amount.']);
            }

            $locked->update([
                'status' => RefundRequest::STATUS_APPROVED,
                'amount' => round((float) $validated['amount'], 2),
                'refund_method' => $validated['refund_method'],
                'notes' => $validated['notes'] ?? $locked->notes,
                'handled_by_admin_id' => auth('admin')->id(),
                'approved_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            return $locked->fresh(['payment.booking.customer', 'payment.booking.guestDetail']);
        }, 3);

        $this->sendStatusMail($refund);

        return redirect()->route('admin.refunds.show', $refund)->with('status', 'Refund request approved.');
    }

    public function reject(Request $request, RefundRequest $refund)
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $refund = DB::transaction(function () use ($refund, $validated): RefundRequest {
            $locked = RefundRequest::query()->with('payment')->lockForUpdate()->findOrFail($refund->id);
            if (! $locked->canTransitionTo(RefundRequest::STATUS_REJECTED)) {
                throw ValidationException::withMessages(['refund' => 'Only pending refund requests can be rejected.']);
            }

            $locked->update([
                'status' => RefundRequest::STATUS_REJECTED,
                'rejection_reason' => trim($validated['rejection_reason']),
                'handled_by_admin_id' => auth('admin')->id(),
                'rejected_at' => now(),
            ]);
            $locked->payment->update(['status' => 'paid']);

            return $locked->fresh(['payment.booking.customer', 'payment.booking.guestDetail']);
        }, 3);

        $this->sendStatusMail($refund);

        return redirect()->route('admin.refunds.show', $refund)->with('status', 'Refund request rejected.');
    }

    public function process(Request $request, RefundRequest $refund)
    {
        $validated = $request->validate([
            'transaction_reference' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $refund = DB::transaction(function () use ($refund, $validated): RefundRequest {
            $locked = RefundRequest::query()->with('payment')->lockForUpdate()->findOrFail($refund->id);
            if (! $locked->canTransitionTo(RefundRequest::STATUS_PROCESSED)) {
                throw ValidationException::withMessages(['refund' => 'Only approved refund requests can be completed.']);
            }

            $reference = strtoupper(trim($validated['transaction_reference']));
            $duplicate = RefundRequest::query()
                ->where('transaction_reference', $reference)
                ->whereKeyNot($locked->id)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['transaction_reference' => 'This refund reference is already in use.']);
            }

            $locked->update([
                'status' => RefundRequest::STATUS_PROCESSED,
                'transaction_reference' => $reference,
                'notes' => $validated['notes'] ?? $locked->notes,
                'handled_by_admin_id' => auth('admin')->id(),
                'processed_at' => now(),
            ]);
            $locked->payment->update(['status' => 'refunded']);

            return $locked->fresh(['payment.booking.customer', 'payment.booking.guestDetail']);
        }, 3);

        $this->sendStatusMail($refund);

        return redirect()->route('admin.refunds.show', $refund)->with('status', 'Refund marked as completed.');
    }

    private function sendStatusMail(RefundRequest $refund): void
    {
        $email = trim($refund->payment->booking->guestEmail());
        if ($email === '' || $email === '-') {
            return;
        }

        try {
            Mail::to($email)->queue(new RefundStatusMail($refund));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
