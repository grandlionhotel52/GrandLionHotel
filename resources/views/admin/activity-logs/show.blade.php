@extends('layouts.admin')

@section('title', 'Activity Log Details')

@push('head')
    <style>
        .audit-detail-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.8rem; }
        .audit-detail-item { min-width: 0; border: 1px solid var(--admin-line); border-radius: 12px; background: #fbfdff; padding: 0.9rem; }
        .audit-detail-icon { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; margin-bottom: 0.55rem; border-radius: 9px; background: var(--admin-surface-soft); color: var(--admin-brand-dark); }
        .audit-value { overflow-wrap: anywhere; white-space: pre-wrap; }
        .audit-event-banner { display: flex; align-items: flex-start; gap: 0.9rem; border: 1px solid var(--admin-line); border-radius: 14px; background: linear-gradient(135deg, #fbfdff, #f6f9ff); padding: 1rem; }
        .audit-event-mark { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; width: 42px; height: 42px; border-radius: 12px; font-size: 1.05rem; }
        .audit-tone-success { background: #e8f6ee; color: #075f3c; }
        .audit-tone-info { background: #eef4ff; color: #174ea6; }
        .audit-tone-danger { background: #fff0ed; color: #981b15; }
        .audit-tone-neutral { background: #f1f3f5; color: #495057; }
        .audit-change-table td, .audit-change-table th { vertical-align: top; }
        .audit-change-table code { display: block; min-width: 150px; border-radius: 8px; background: #f8fafc; padding: 0.55rem; color: #26354a; }
        @media (max-width: 991.98px) { .audit-detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 575.98px) { .audit-detail-grid { grid-template-columns: 1fr; } }
    </style>
@endpush

@section('content')
    @php
        $changes = is_array($activityLog->changes) ? $activityLog->changes : [];
        $isStructured = array_key_exists('before', $changes) || array_key_exists('after', $changes);
        $before = $isStructured && is_array($changes['before'] ?? null) ? $changes['before'] : [];
        $after = $isStructured && is_array($changes['after'] ?? null) ? $changes['after'] : $changes;
        $fields = collect(array_merge(array_keys($before), array_keys($after)))->reject(fn ($field) => in_array($field, ['created_at', 'updated_at'], true))->unique()->values();
        $actorKey = $activityLog->actor_type.':'.$activityLog->actor_id;
        $subjectKey = $activityLog->subject_type.':'.$activityLog->subject_id;
        $actorName = $actorLabels[$actorKey] ?? null;
        $subjectLabel = $subjectLabels[$subjectKey] ?? $activityLog->subject_type.' #'.$activityLog->subject_id;
        $events = [
            'created' => ['label' => 'Created', 'verb' => 'created', 'icon' => 'bi-plus-lg', 'tone' => 'success'],
            'updated' => ['label' => 'Updated', 'verb' => 'updated', 'icon' => 'bi-pencil', 'tone' => 'info'],
            'deleted' => ['label' => 'Deleted', 'verb' => 'deleted', 'icon' => 'bi-trash3', 'tone' => 'danger'],
            'logged_in' => ['label' => 'Signed in', 'verb' => 'signed in as', 'icon' => 'bi-box-arrow-in-right', 'tone' => 'success'],
            'logged_out' => ['label' => 'Signed out', 'verb' => 'signed out as', 'icon' => 'bi-box-arrow-right', 'tone' => 'danger'],
        ];
        $event = $events[$activityLog->action] ?? ['label' => str($activityLog->action)->headline(), 'verb' => str($activityLog->action)->replace('_', ' '), 'icon' => 'bi-circle', 'tone' => 'neutral'];
        $formatValue = static function ($value): string {
            if ($value === null) return 'null';
            if (is_bool($value)) return $value ? 'true' : 'false';
            if (is_array($value) || is_object($value)) return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—';
            if ($value === '') return '(empty)';
            return (string) $value;
        };
        $deviceLabel = static function (?string $userAgent): string {
            if (blank($userAgent)) return 'Not available';
            $browser = match (true) {
                str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
                str_contains($userAgent, 'Chrome/') => 'Google Chrome',
                str_contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
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
            <p class="small text-secondary mb-1">Audit Event #{{ $activityLog->activity_log_id }}</p>
            <h1 class="h3 mb-1">{{ $event['label'] }} {{ str($activityLog->subject_type)->headline() }}</h1>
            <p class="text-secondary mb-0">{{ $activityLog->created_at?->format('M d, Y \a\t h:i:s A') }} · {{ $activityLog->created_at?->diffForHumans() }}</p>
        </div>
        <x-back-button :href="route('admin.activity-logs.index', request()->query())" label="Back to activity logs" />
    </section>

    <section class="audit-event-banner mb-4">
        <span class="audit-event-mark audit-tone-{{ $event['tone'] }}"><i class="bi {{ $event['icon'] }}"></i></span>
        <div>
            <strong class="d-block">{{ $actorName ?: ($activityLog->actor_type ?: 'System') }} {{ $event['verb'] }} {{ $subjectLabel }}.</strong>
            <span class="small text-secondary">This immutable event records who performed the action, its source, and the captured field changes.</span>
        </div>
    </section>

    <section class="soft-card p-3 p-lg-4 mb-4">
        <div class="audit-detail-grid">
            <div class="audit-detail-item"><span class="audit-detail-icon"><i class="bi bi-person"></i></span><small class="text-secondary d-block">Performed by</small><strong>{{ $actorName ?: ($activityLog->actor_type ?: 'System') }}</strong><div class="small text-secondary">{{ $activityLog->actor_type ?: 'Automated process' }}</div></div>
            <div class="audit-detail-item"><span class="audit-detail-icon"><i class="bi bi-bullseye"></i></span><small class="text-secondary d-block">Affected record</small><strong class="audit-value">{{ $subjectLabel }}</strong><div class="small text-secondary">{{ str($activityLog->subject_type)->headline() }} #{{ $activityLog->subject_id }}</div></div>
            <div class="audit-detail-item"><span class="audit-detail-icon"><i class="bi bi-clock"></i></span><small class="text-secondary d-block">Occurred</small><strong>{{ $activityLog->created_at?->format('M d, Y') }}</strong><div class="small text-secondary">{{ $activityLog->created_at?->format('h:i:s A') }}</div></div>
            <div class="audit-detail-item"><span class="audit-detail-icon"><i class="bi bi-globe2"></i></span><small class="text-secondary d-block">IP address</small><strong>{{ $activityLog->ip_address ?: 'Not available' }}</strong></div>
            <div class="audit-detail-item"><span class="audit-detail-icon"><i class="bi bi-laptop"></i></span><small class="text-secondary d-block">Browser / device</small><strong>{{ $deviceLabel($activityLog->user_agent) }}</strong><div class="audit-value small text-secondary mt-1">{{ $activityLog->user_agent ?: 'User agent not recorded' }}</div></div>
            <div class="audit-detail-item"><span class="audit-detail-icon"><i class="bi bi-fingerprint"></i></span><small class="text-secondary d-block">Event identifier</small><strong>#{{ $activityLog->activity_log_id }}</strong><div class="small text-secondary">{{ $event['label'] }}</div></div>
        </div>
    </section>

    <section class="soft-card p-3 p-lg-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div><h2 class="h5 mb-1">Before and after</h2><p class="small text-secondary mb-0">Sensitive values are automatically redacted.</p></div>
            <span class="badge rounded-pill text-bg-light border">{{ $fields->count() }} {{ str('field')->plural($fields->count()) }}</span>
        </div>
        @if($fields->isEmpty())
            <div class="text-center py-4"><i class="bi bi-info-circle fs-3 text-secondary"></i><p class="text-secondary mb-0 mt-2">This event did not record field-level changes.</p></div>
        @else
            <div class="table-responsive">
                <table class="table audit-change-table align-middle">
                    <thead><tr><th>Field</th><th>Previous value</th><th>New value</th></tr></thead>
                    <tbody>
                        @foreach($fields as $field)
                            <tr>
                                <th>{{ str($field)->headline() }}</th>
                                <td><code class="audit-value">{{ array_key_exists($field, $before) ? $formatValue($before[$field]) : '—' }}</code></td>
                                <td><code class="audit-value">{{ array_key_exists($field, $after) ? $formatValue($after[$field]) : '—' }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
