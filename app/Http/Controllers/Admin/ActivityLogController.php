<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    private const ACTIONS = ['created', 'updated', 'deleted', 'logged_in', 'logged_out'];

    private const SUBJECT_TYPES = ['Booking', 'Payment', 'Room', 'Admin', 'Staff', 'Customer'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'in:'.implode(',', self::ACTIONS)],
            'subject_type' => ['nullable', 'in:'.implode(',', self::SUBJECT_TYPES)],
            'actor_type' => ['nullable', 'in:Admin,Staff,Customer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = ActivityLog::query()
            ->when(filled($filters['action'] ?? null), fn (Builder $query) => $query->where('action', $filters['action']))
            ->when(filled($filters['subject_type'] ?? null), fn (Builder $query) => $query->where('subject_type', $filters['subject_type']))
            ->when(filled($filters['actor_type'] ?? null), fn (Builder $query) => $query->where('actor_type', $filters['actor_type']))
            ->when(filled($filters['from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['q']);
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('subject_type', 'like', '%'.$search.'%')
                        ->orWhere('actor_type', 'like', '%'.$search.'%')
                        ->orWhere('action', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%')
                        ->orWhere('user_agent', 'like', '%'.$search.'%')
                        ->orWhere('changes', 'like', '%'.$search.'%');

                    foreach ($this->matchingActors($search) as $actorType => $actorIds) {
                        $nested->orWhere(function (Builder $actorQuery) use ($actorType, $actorIds): void {
                            $actorQuery->where('actor_type', $actorType)
                                ->whereIn('actor_id', $actorIds);
                        });
                    }

                    if (ctype_digit($search)) {
                        $nested->orWhere('subject_id', (int) $search)
                            ->orWhere('actor_id', (int) $search);
                    }
                });
            });

        $logs = $query
            ->latest('activity_log_id')
            ->paginate(25)
            ->withQueryString();

        $actorLabels = $this->actorLabels($logs->getCollection());
        $subjectLabels = $this->subjectLabels($logs->getCollection());
        $today = now()->startOfDay();
        $summary = [
            'today' => ActivityLog::query()->where('created_at', '>=', $today)->count(),
            'changes' => ActivityLog::query()->where('created_at', '>=', $today)->whereIn('action', ['created', 'updated', 'deleted'])->count(),
            'sign_ins' => ActivityLog::query()->where('created_at', '>=', $today)->where('action', 'logged_in')->count(),
            'actors' => ActivityLog::query()
                ->where('created_at', '>=', $today)
                ->whereNotNull('actor_type')
                ->whereNotNull('actor_id')
                ->get(['actor_type', 'actor_id'])
                ->unique(fn (ActivityLog $log): string => $log->actor_type.':'.$log->actor_id)
                ->count(),
        ];

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'actorLabels' => $actorLabels,
            'subjectLabels' => $subjectLabels,
            'summary' => $summary,
            'actions' => self::ACTIONS,
            'subjectTypes' => self::SUBJECT_TYPES,
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        $logs = collect([$activityLog]);
        $actorLabels = $this->actorLabels($logs);
        $subjectLabels = $this->subjectLabels($logs);

        return view('admin.activity-logs.show', compact('activityLog', 'actorLabels', 'subjectLabels'));
    }

    private function actorLabels(Collection $logs): array
    {
        $models = [
            'Admin' => Admin::class,
            'Staff' => Staff::class,
            'Customer' => Customer::class,
        ];
        $labels = [];

        foreach ($models as $type => $modelClass) {
            $ids = $logs
                ->where('actor_type', $type)
                ->pluck('actor_id')
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                continue;
            }

            foreach ($modelClass::query()->whereKey($ids)->get(['name', (new $modelClass)->getKeyName()]) as $account) {
                $labels[$type.':'.$account->getKey()] = $account->name;
            }
        }

        foreach ($logs as $log) {
            $key = $log->actor_type.':'.$log->actor_id;
            if (isset($labels[$key]) || $log->actor_type !== $log->subject_type || $log->actor_id !== $log->subject_id) {
                continue;
            }

            $labels[$key] = $this->labelFromChanges($log) ?? $log->actor_type;
        }

        return $labels;
    }

    private function subjectLabels(Collection $logs): array
    {
        $labels = [];

        $this->addSimpleSubjectLabels($labels, $logs, 'Room', Room::class, 'name');
        $this->addSimpleSubjectLabels($labels, $logs, 'Admin', Admin::class, 'name');
        $this->addSimpleSubjectLabels($labels, $logs, 'Staff', Staff::class, 'name');
        $this->addSimpleSubjectLabels($labels, $logs, 'Customer', Customer::class, 'name');

        $bookingIds = $this->subjectIds($logs, 'Booking');
        if ($bookingIds->isNotEmpty()) {
            foreach (Booking::query()->with(['customer', 'guestDetail'])->whereKey($bookingIds)->get() as $booking) {
                $labels['Booking:'.$booking->getKey()] = 'Booking #'.$booking->getKey().' · '.$booking->guestName();
            }
        }

        $paymentIds = $this->subjectIds($logs, 'Payment');
        if ($paymentIds->isNotEmpty()) {
            foreach (Payment::query()->whereKey($paymentIds)->get(['payment_id', 'transaction_reference']) as $payment) {
                $reference = trim((string) $payment->transaction_reference);
                $labels['Payment:'.$payment->getKey()] = $reference !== '' ? $reference : 'Payment #'.$payment->getKey();
            }
        }

        foreach ($logs as $log) {
            $key = $log->subject_type.':'.$log->subject_id;
            $labels[$key] ??= $this->labelFromChanges($log) ?? $log->subject_type.' #'.$log->subject_id;
        }

        return $labels;
    }

    private function addSimpleSubjectLabels(array &$labels, Collection $logs, string $type, string $modelClass, string $column): void
    {
        $ids = $this->subjectIds($logs, $type);
        if ($ids->isEmpty()) {
            return;
        }

        $model = new $modelClass;
        foreach ($modelClass::query()->whereKey($ids)->get([$model->getKeyName(), $column]) as $record) {
            $labels[$type.':'.$record->getKey()] = (string) $record->{$column};
        }
    }

    private function subjectIds(Collection $logs, string $type): Collection
    {
        return $logs
            ->where('subject_type', $type)
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function labelFromChanges(ActivityLog $log): ?string
    {
        $changes = is_array($log->changes) ? $log->changes : [];
        $values = array_merge(
            is_array($changes['before'] ?? null) ? $changes['before'] : [],
            is_array($changes['after'] ?? null) ? $changes['after'] : $changes,
        );

        foreach (['name', 'transaction_reference', 'email', 'username'] as $field) {
            $value = trim((string) ($values[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function matchingActors(string $search): array
    {
        $matches = [];

        foreach ([
            'Admin' => [Admin::class, 'email'],
            'Staff' => [Staff::class, 'username'],
            'Customer' => [Customer::class, 'email'],
        ] as $type => [$modelClass, $loginColumn]) {
            $ids = $modelClass::query()
                ->where(function (Builder $query) use ($search, $loginColumn): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere($loginColumn, 'like', '%'.$search.'%');
                })
                ->limit(100)
                ->pluck((new $modelClass)->getKeyName())
                ->all();

            if ($ids !== []) {
                $matches[$type] = $ids;
            }
        }

        return $matches;
    }
}
