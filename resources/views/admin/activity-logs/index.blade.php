@extends('layouts.admin')

@section('title', 'Activity Logs')

@push('head')
    <style>
        .audit-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
        }
        .audit-summary-card {
            border: 1px solid var(--admin-line);
            border-radius: 13px;
            background: #fff;
            padding: 0.85rem 1rem;
        }
        .audit-event {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.28rem 0.58rem;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: capitalize;
        }
        .audit-event-created,
        .audit-event-logged_in {
            background: #e8f6ee;
            color: #075f3c;
        }
        .audit-event-updated {
            background: #eef4ff;
            color: #174ea6;
        }
        .audit-event-deleted,
        .audit-event-logged_out {
            background: #fff0ed;
            color: #981b15;
        }
        .audit-change-list {
            max-width: 290px;
            color: var(--admin-muted);
            font-size: 0.8rem;
        }
        @media (max-width: 767.98px) {
            .audit-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endpush

@section('content')
    <section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="small text-secondary mb-1">Security &amp; Accountability</p>
            <h1 class="h3 mb-1">Activity Logs</h1>
            <p class="text-secondary mb-0">Review who changed operational records, what changed, and where the request originated.</p>
        </div>
        <span class="badge text-bg-dark">{{ number_format($logs->total()) }} matching events</span>
    </section>

    <section class="soft-card p-3 mb-4">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label" for="auditSearch">Search</label>
                <input id="auditSearch" class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Type, ID, action, or IP">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label" for="auditAction">Action</label>
                <select id="auditAction" class="form-select" name="action">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label" for="auditTarget">Target</label>
                <select id="auditTarget" class="form-select" name="subject_type">
                    <option value="">All targets</option>
                    @foreach($subjectTypes as $subjectType)
                        <option value="{{ $subjectType }}" @selected(request('subject_type') === $subjectType)>{{ str($subjectType)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label" for="auditActor">Actor role</label>
                <select id="auditActor" class="form-select" name="actor_type">
                    <option value="">All roles</option>
                    @foreach(['Admin', 'Staff', 'Customer'] as $actorType)
                        <option value="{{ $actorType }}" @selected(request('actor_type') === $actorType)>{{ $actorType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-1">
                <label class="form-label" for="auditFrom">From</label>
                <input id="auditFrom" class="form-control" type="date" name="from" value="{{ request('from') }}">
            </div>
            <div class="col-sm-6 col-lg-1">
                <label class="form-label" for="auditTo">To</label>
                <input id="auditTo" class="form-control" type="date" name="to" value="{{ request('to') }}">
            </div>
            <div class="col-sm-6 col-lg-1 d-grid gap-1">
                <button class="btn btn-ta btn-sm" type="submit">Filter</button>
                <a class="btn btn-ta-outline btn-sm" href="{{ route('admin.activity-logs.index') }}">Clear</a>
            </div>
        </form>
    </section>

    <section class="table-shell">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Date &amp; time</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Changed fields</th>
                        <th>IP address</th>
                        <th class="text-end">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $changes = is_array($log->changes) ? $log->changes : [];
                            $after = is_array($changes['after'] ?? null) ? $changes['after'] : $changes;
                            $changedFields = array_keys($after);
                            $actorKey = $log->actor_type.':'.$log->actor_id;
                            $actorName = $actorLabels[$actorKey] ?? null;
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                <strong>{{ $log->created_at?->format('M d, Y') }}</strong>
                                <small class="d-block text-secondary">{{ $log->created_at?->format('h:i:s A') }}</small>
                            </td>
                            <td>
                                <strong>{{ $actorName ?: ($log->actor_type ?: 'System') }}</strong>
                                <small class="d-block text-secondary">
                                    {{ $log->actor_type ? $log->actor_type.' #'.$log->actor_id : 'Automated process' }}
                                </small>
                            </td>
                            <td>
                                <span class="audit-event audit-event-{{ $log->action }}">
                                    {{ str($log->action)->replace('_', ' ') }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ str($log->subject_type)->headline() }}</strong>
                                <small class="d-block text-secondary">#{{ $log->subject_id }}</small>
                            </td>
                            <td>
                                <div class="audit-change-list">
                                    {{ $changedFields !== [] ? collect($changedFields)->map(fn ($field) => str($field)->headline())->join(', ') : 'No field values recorded' }}
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $log->ip_address ?: '—' }}</td>
                            <td class="text-end">
                                <a class="btn btn-ta-outline btn-sm" href="{{ route('admin.activity-logs.show', $log) }}">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">No activity matches the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($logs->hasPages())
        <div class="mt-3">{{ $logs->links() }}</div>
    @endif
@endsection
