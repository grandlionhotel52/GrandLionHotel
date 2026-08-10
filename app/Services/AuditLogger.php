<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'remember_token',
        'payment_proof_path',
        'discount_id_photo_path',
        'reset_token',
        'token',
    ];

    public function recordModel(Model $model, string $action, array $before = [], array $after = []): ?ActivityLog
    {
        if (! Schema::hasTable('activity_logs')) {
            return null;
        }

        $actor = $this->resolveActor();

        return ActivityLog::query()->create([
            'actor_type' => $actor ? class_basename($actor) : null,
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => class_basename($model),
            'subject_id' => $model->getKey(),
            'changes' => [
                'before' => $this->sanitize($before),
                'after' => $this->sanitize($after),
            ],
            'ip_address' => $this->requestValue('ip'),
            'user_agent' => $this->requestValue('userAgent'),
        ]);
    }

    public function recordAuthentication(Model $account, string $action, ?string $guard = null): ?ActivityLog
    {
        if (! Schema::hasTable('activity_logs')) {
            return null;
        }

        return ActivityLog::query()->create([
            'actor_type' => class_basename($account),
            'actor_id' => $account->getKey(),
            'action' => $action,
            'subject_type' => class_basename($account),
            'subject_id' => $account->getKey(),
            'changes' => [
                'before' => [],
                'after' => array_filter(['guard' => $guard]),
            ],
            'ip_address' => $this->requestValue('ip'),
            'user_agent' => $this->requestValue('userAgent'),
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

    private function sanitize(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_FIELDS, true)) {
                $values[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }

    private function requestValue(string $method): ?string
    {
        if (app()->runningInConsole() || ! app()->bound('request')) {
            return null;
        }

        $value = request()->{$method}();

        return filled($value) ? (string) $value : null;
    }
}
