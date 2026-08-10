<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    private const ACTIONS = ['created', 'updated', 'deleted', 'logged_in', 'logged_out'];

    private const SUBJECT_TYPES = ['Booking', 'Payment', 'RefundRequest', 'Room', 'Admin', 'Staff', 'Customer'];

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

        $logs = ActivityLog::query()
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
                        ->orWhere('ip_address', 'like', '%'.$search.'%');

                    if (ctype_digit($search)) {
                        $nested->orWhere('subject_id', (int) $search)
                            ->orWhere('actor_id', (int) $search);
                    }
                });
            })
            ->latest('activity_log_id')
            ->paginate(25)
            ->withQueryString();

        $actorLabels = $this->actorLabels($logs->getCollection());

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'actorLabels' => $actorLabels,
            'actions' => self::ACTIONS,
            'subjectTypes' => self::SUBJECT_TYPES,
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        $actorLabels = $this->actorLabels(collect([$activityLog]));

        return view('admin.activity-logs.show', compact('activityLog', 'actorLabels'));
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

        return $labels;
    }
}
