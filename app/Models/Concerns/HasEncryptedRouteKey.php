<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

trait HasEncryptedRouteKey
{
    public function getRouteKey(): mixed
    {
        return self::encryptRouteKey((string) $this->getKey());
    }

    public function resolveRouteBinding($value, $field = null): mixed
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        $key = self::decryptRouteKey((string) $value);

        if ($key === null) {
            throw (new ModelNotFoundException)->setModel(static::class, [$value]);
        }

        return $this->where($this->getRouteKeyName(), $key)->firstOrFail();
    }

    public static function encryptRouteKey(string|int $key): string
    {
        $key = (string) $key;
        $signature = hash_hmac('sha256', $key, self::routeSigningKey());

        return rtrim(strtr(base64_encode($key.'.'.$signature), '+/', '-_'), '=');
    }

    public static function decryptRouteKey(string $value): ?string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $payload = base64_decode(strtr($value, '-_', '+/'), true);
        if ($payload === false) {
            return null;
        }

        if (preg_match('/^(\d+)\.([a-f0-9]{64})$/', $payload, $matches) === 1) {
            $key = $matches[1];
            $expectedSignature = hash_hmac('sha256', $key, self::routeSigningKey());

            return hash_equals($expectedSignature, $matches[2]) ? $key : null;
        }

        if (Str::startsWith($payload, 'eyJpdiI6')) {
            try {
                $legacyKey = Crypt::decryptString($payload);
            } catch (DecryptException) {
                return null;
            }

            return ctype_digit($legacyKey) ? $legacyKey : null;
        }

        return null;
    }

    private static function routeSigningKey(): string
    {
        $appKey = (string) config('app.key');

        return Str::startsWith($appKey, 'base64:')
            ? (base64_decode(Str::after($appKey, 'base64:'), true) ?: $appKey)
            : $appKey;
    }
}
