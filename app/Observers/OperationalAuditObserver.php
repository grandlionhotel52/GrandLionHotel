<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Notifications\BookingActivityNotification;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class OperationalAuditObserver
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function created(Model $model): void
    {
        $this->auditLogger->recordModel($model, 'created', [], $model->getAttributes());
        $this->notifyCustomer($model, 'created');
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $before = [];
        foreach (array_keys($changes) as $key) {
            $before[$key] = $model->getOriginal($key);
        }

        $this->auditLogger->recordModel($model, 'updated', $before, $changes);
        $this->notifyCustomer($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->auditLogger->recordModel($model, 'deleted', $model->getOriginal(), []);
    }

    private function notifyCustomer(Model $model, string $event): void
    {
        $booking = match (true) {
            $model instanceof Booking => $model,
            $model instanceof Payment => $model->booking,
            $model instanceof RefundRequest => $model->payment?->booking,
            default => null,
        };

        if (! $booking?->customer) {
            return;
        }

        $relevantChanges = array_keys($model->getChanges());
        if ($event === 'updated' && array_intersect($relevantChanges, ['status', 'method', 'paid_at', 'verified_at']) === []) {
            return;
        }

        $booking->customer->notify(new BookingActivityNotification($booking, $model, $event));
    }
}
