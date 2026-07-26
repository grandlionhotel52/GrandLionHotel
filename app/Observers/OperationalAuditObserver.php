<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Notifications\BookingActivityNotification;
use Illuminate\Database\Eloquent\Model;

class OperationalAuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created', $model->getAttributes());
        $this->notifyCustomer($model, 'created');
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $this->record($model, 'updated', $changes);
        $this->notifyCustomer($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted', []);
    }

    private function record(Model $model, string $action, array $changes): void
    {
        $actor = $this->resolveActor();

        ActivityLog::query()->create([
            'actor_type' => $actor ? class_basename($actor) : null,
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => class_basename($model),
            'subject_id' => $model->getKey(),
            'changes' => $this->sanitize($changes),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
        ]);
    }

    private function resolveActor(): ?Model
    {
        foreach (['admin', 'staff', 'customer'] as $guard) {
            $actor = auth($guard)->user();
            if ($actor instanceof Model) {
                return $actor;
            }
        }

        return null;
    }

    private function sanitize(array $changes): array
    {
        foreach (['password', 'remember_token', 'payment_proof_path'] as $sensitive) {
            if (array_key_exists($sensitive, $changes)) {
                $changes[$sensitive] = '[redacted]';
            }
        }

        return $changes;
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
