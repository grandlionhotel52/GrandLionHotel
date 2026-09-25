@extends('layouts.admin')

@section('title', 'Activity Logs')

@push('head')
    <style>
        .audit-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; }
        .audit-summary-card { display: flex; align-items: center; gap: 0.8rem; min-width: 0; border: 1px solid var(--admin-line); border-radius: 14px; background: #fff; padding: 1rem; }
        .audit-summary-icon, .audit-event-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; border-radius: 12px; }
        .audit-summary-icon { width: 42px; height: 42px; color: var(--admin-brand-dark); background: var(--admin-surface-soft); font-size: 1.05rem; }
        .audit-summary-value { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
        .audit-event-cell { display: flex; align-items: flex-start; gap: 0.7rem; min-width: 205px; }
        .audit-event-icon { width: 34px; height: 34px; margin-top: 0.05rem; }
        .audit-tone-success { background: #e8f6ee; color: #075f3c; }
        .audit-tone-info { background: #eef4ff; color: #174ea6; }
        .audit-tone-danger { background: #fff0ed; color: #981b15; }
        .audit-tone-neutral { background: #f1f3f5; color: #495057; }
        .audit-event { display: inline-flex; align-items: center; border-radius: 999px; padding: 0.25rem 0.55rem; font-size: 0.7rem; font-weight: 800; line-height: 1; }
        .audit-identity, .audit-target { min-width: 145px; max-width: 230px; }
        .audit-primary { display: block; overflow: hidden; color: var(--admin-ink); font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .audit-meta { color: var(--admin-muted); font-size: 0.76rem; }
        .audit-field-list { display: flex; flex-wrap: wrap; gap: 0.3rem; min-width: 170px; max-width: 280px; }
        .audit-field { border: 1px solid var(--admin-line); border-radius: 999px; background: #f8fafc; padding: 0.16rem 0.45rem; color: #475569; font-size: 0.7rem; font-weight: 700; }
        .audit-source { min-width: 135px; max-width: 200px; }
        .audit-empty { padding: 4rem 1rem !important; }
        @media (max-width: 991.98px) { .audit-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 575.98px) { .audit-summary { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    @php
        $actionPresentation = [
            'created' => ['label' => 'Created', 'icon' => 'bi-plus-lg', 'tone' => 'success'],
            'updated' => ['label' => 'Updated', 'icon' => 'bi-pencil', 'tone' => 'info'],
            'deleted' => ['label' => 'Deleted', 'icon' => 'bi-trash3', 'tone' => 'danger'],
            'logged_in' => ['label' => 'Signed in', 'icon' => 'bi-box-arrow-in-right', 'tone' => 'success'],
            'logged_out' => ['label' => 'Signed out', 'icon' => 'bi-box-arrow-right', 'tone' => 'danger'],
        ];
        $deviceLabel = static function (?string $userAgent): string {
            if (blank($userAgent)) {
                return 'Unknown device';
            }

            $browser = match (true) {
                str_contains($userAgent, 'Edg/') => 'Edge',
                str_contains($userAgent, 'Chrome/') => 'Chrome',
                str_contains($userAgent, 'Firefox/') => 'Firefox',
                str_contains($userAgent, 'Safari/') => 'Safari',
                default => 'Other browser',
            };
            $platform = match (true) {
                str_contains($userAgent, 'Windows') => 'Windows',
                str_contains($userAgent, 'Android') => 'Android',
                str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
                str_contains($userAgent, 'Macintosh') => 'macOS',
                str_contains($userAgent, 'Linux') => 'Linux',
                default => 'Unknown OS',
            };

            return $browser.' on '.$platform;
        };
    @endphp

    <section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="small text-secondary mb-1">Security &amp; Accountability</p>
            <h1 class="h3 mb-1">Activity Logs</h1>
            <p class="text-secondary mb-0">A chronological audit trail of account access and operational changes.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-dark">{{ number_format($logs->total()) }} matching events</span>
            <a class="btn btn-ta-outline btn-sm" href="{{ request()->fullUrl() }}" aria-label="Refresh activity logs">
                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
            </a>
        </div>
    </section>

    <section class="audit-summary mb-4" aria-label="Today's activity summary">
        <article class="audit-summary-card"><span class="audit-summary-icon"><i class="bi bi-activity"></i></span><div><div class="audit-summary-value">{{ number_format($summary['today']) }}</div><div class="audit-meta">Events today</div></div></article>
        <article class="audit-summary-card"><span class="audit-summary-icon"><i class="bi bi-pencil-square"></i></span><div><div class="audit-summary-value">{{ number_format($summary['changes']) }}</div><div class="audit-meta">Record changes</div></div></article>
        <article class="audit-summary-card"><span class="audit-summary-icon"><i class="bi bi-box-arrow-in-right"></i></span><div><div class="audit-summary-value">{{ number_format($summary['sign_ins']) }}</div><div class="audit-meta">Successful sign-ins</div></div></article>
        <article class="audit-summary-card"><span class="audit-summary-icon"><i class="bi bi-people"></i></span><div><div class="audit-summary-value">{{ number_format($summary['actors']) }}</div><div class="audit-meta">Active accounts</div></div></article>
    </section>

    <section class="soft-card p-3 mb-4">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label" for="auditSearch">Search activity</label>
                <input id="auditSearch" class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Name, email, record ID, IP...">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label" for="auditAction">Event</label>
                <select id="auditAction" class="form-select" name="action">
                    <option value="">All events</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $actionPresentation[$action]['label'] ?? str($action)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label" for="auditTarget">Record type</label>
                <select id="auditTarget" class="form-select" name="subject_type">
                    <option value="">All record types</option>
                    @foreach($subjectTypes as $subjectType)
                        <option value="{{ $subjectType }}" @selected(request('subject_type') === $subjectType)>{{ str($subjectType)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label" for="auditActor">Performed by</label>
                <select id="auditActor" class="form-select" name="actor_type">
                    <option value="">All account types</option>
                    @foreach(['Admin', 'Staff', 'Customer'] as $actorType)
                        <option value="{{ $actorType }}" @selected(request('actor_type') === $actorType)>{{ $actorType }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-1"><label class="form-label" for="auditFrom">From</label><input id="auditFrom" class="form-control" type="date" name="from" value="{{ request('from') }}"></div>
            <div class="col-sm-6 col-lg-1"><label class="form-label" for="auditTo">To</label><input id="auditTo" class="form-control" type="date" name="to" value="{{ request('to') }}"></div>
            <div class="col-sm-6 col-lg-1 d-grid gap-1">
                <button class="btn btn-ta btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Apply</button>
                <a class="btn btn-ta-outline btn-sm" href="{{ route('admin.activity-logs.index') }}">Reset</a>
            </div>
        </form>
    </section>

    <section class="table-shell">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Event</th><th>Performed by</th><th>Record</th><th>Changes</th><th>Source</th><th>Occurred</th><th class="text-end"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $changes = is_array($log->changes) ? $log->changes : [];
                            $before = is_array($changes['before'] ?? null) ? $changes['before'] : [];
                            $after = is_array($changes['after'] ?? null) ? $changes['after'] : $changes;
                            $changedFields = collect(array_merge(array_keys($before), array_keys($after)))->reject(fn ($field) => in_array($field, ['created_at', 'updated_at'], true))->unique()->values();
                            $actorKey = $log->actor_type.':'.$log->actor_id;
                            $subjectKey = $log->subject_type.':'.$log->subject_id;
                            $actorName = $actorLabels[$actorKey] ?? null;
                            $subjectLabel = $subjectLabels[$subjectKey] ?? $log->subject_type.' #'.$log->subject_id;
                            $event = $actionPresentation[$log->action] ?? ['label' => str($log->action)->headline(), 'icon' => 'bi-circle', 'tone' => 'neutral'];
                        @endphp
                        <tr>
                            <td><div class="audit-event-cell"><span class="audit-event-icon audit-tone-{{ $event['tone'] }}"><i class="bi {{ $event['icon'] }}"></i></span><div><span class="audit-event audit-tone-{{ $event['tone'] }}">{{ $event['label'] }}</span><div class="audit-meta mt-1">Event #{{ $log->activity_log_id }}</div></div></div></td>
                            <td><div class="audit-identity"><span class="audit-primary">{{ $actorName ?: ($log->actor_type ?: 'System') }}</span><span class="audit-meta">{{ $log->actor_type ?: 'Automated process' }}</span></div></td>
                            <td><div class="audit-target"><span class="audit-primary" title="{{ $subjectLabel }}">{{ $subjectLabel }}</span><span class="audit-meta">{{ str($log->subject_type)->headline() }} #{{ $log->subject_id }}</span></div></td>
                            <td><div class="audit-field-list">
                                @forelse($changedFields->take(3) as $field)<span class="audit-field">{{ str($field)->headline() }}</span>@empty<span class="audit-meta">No field changes</span>@endforelse
                                @if($changedFields->count() > 3)<span class="audit-field">+{{ $changedFields->count() - 3 }} more</span>@endif
                            </div></td>
                            <td><div class="audit-source"><span class="audit-primary">{{ $log->ip_address ?: 'Not available' }}</span><span class="audit-meta">{{ $deviceLabel($log->user_agent) }}</span></div></td>
                            <td class="text-nowrap"><strong>{{ $log->created_at?->format('M d, Y') }}</strong><span class="audit-meta d-block">{{ $log->created_at?->format('h:i:s A') }}</span><span class="audit-meta d-block">{{ $log->created_at?->diffForHumans() }}</span></td>
                            <td class="text-end"><a class="btn btn-ta-outline btn-sm" href="{{ route('admin.activity-logs.show', $log) }}" aria-label="View event {{ $log->activity_log_id }}">View <i class="bi bi-chevron-right ms-1"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="audit-empty text-center"><i class="bi bi-clock-history fs-2 d-block mb-2 text-secondary"></i><strong>No activity found</strong><p class="text-secondary mb-0">Try adjusting or clearing the selected filters.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($logs->hasPages())
        <div class="mt-3">{{ $logs->links() }}</div>
    @endif
@endsection
