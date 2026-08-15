@extends('layouts.admin')

@section('title', 'Activity Log Details')

@push('head')
    <style>
        .audit-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
        }
        .audit-detail-item {
            border: 1px solid var(--admin-line);
            border-radius: 12px;
            background: #fbfdff;
            padding: 0.8rem;
        }
        .audit-value {
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }
        @media (max-width: 767.98px) {
            .audit-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $changes = is_array($activityLog->changes) ? $activityLog->changes : [];
        $isStructured = array_key_exists('before', $changes) || array_key_exists('after', $changes);
        $before = $isStructured && is_array($changes['before'] ?? null) ? $changes['before'] : [];
        $after = $isStructured && is_array($changes['after'] ?? null) ? $changes['after'] : $changes;
        $fields = collect(array_merge(array_keys($before), array_keys($after)))->unique()->values();
        $actorKey = $activityLog->actor_type.':'.$activityLog->actor_id;
        $actorName = $actorLabels[$actorKey] ?? null;
        $formatValue = static function ($value): string {
            if ($value === null) {
                return 'null';
            }
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—';
            }

            return (string) $value;
        };
    @endphp

    <section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="small text-secondary mb-1">Audit Event #{{ $activityLog->activity_log_id }}</p>
            <h1 class="h3 mb-1">{{ str($activityLog->action)->replace('_', ' ')->title() }} {{ str($activityLog->subject_type)->headline() }}</h1>
            <p class="text-secondary mb-0">{{ $activityLog->created_at?->format('M d, Y \a\t h:i:s A') }}</p>
        </div>
        <x-back-button :href="route('admin.activity-logs.index', request()->query())" label="Back to activity logs" />
    </section>

    <section class="soft-card p-3 p-lg-4 mb-4">
        <div class="audit-detail-grid">
            <div class="audit-detail-item">
                <small class="text-secondary d-block">Actor</small>
                <strong>{{ $actorName ?: ($activityLog->actor_type ?: 'System') }}</strong>
                <div class="small text-secondary">{{ $activityLog->actor_type ? $activityLog->actor_type.' #'.$activityLog->actor_id : 'Automated process' }}</div>
            </div>
            <div class="audit-detail-item">
                <small class="text-secondary d-block">Target record</small>
                <strong>{{ str($activityLog->subject_type)->headline() }} #{{ $activityLog->subject_id }}</strong>
            </div>
            <div class="audit-detail-item">
                <small class="text-secondary d-block">IP address</small>
                <strong>{{ $activityLog->ip_address ?: 'Not available' }}</strong>
            </div>
            <div class="audit-detail-item">
                <small class="text-secondary d-block">Browser / device</small>
                <div class="audit-value small">{{ $activityLog->user_agent ?: 'Not available' }}</div>
            </div>
        </div>
    </section>

    <section class="soft-card p-3 p-lg-4">
        <h2 class="h5 mb-3">Before and after</h2>
        @if($fields->isEmpty())
            <p class="text-secondary mb-0">This event did not record field-level changes.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Before</th>
                            <th>After</th>
                        </tr>
                    </thead>
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
