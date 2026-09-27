<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;

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
        if (!ctype_digit($key) || (int) $key < 1) {
            throw new RuntimeException('Route keys must be positive integers.');
        }

        $packedKey = pack('J', (int) $key);
        $authenticationTag = substr(
            hash_hmac('sha256', static::class.'|'.$packedKey, self::routeAuthenticationKey(), true),
            0,
            8
        );
        $encrypted = openssl_encrypt(
            $packedKey.$authenticationTag,
            'aes-256-ecb',
            self::routeEncryptionKey(),
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
        );

        if ($encrypted === false) {
            throw new RuntimeException('Unable to encrypt the route key.');
        }

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
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

        if (strlen($payload) === 16) {
            $decrypted = openssl_decrypt(
                $payload,
                'aes-256-ecb',
                self::routeEncryptionKey(),
                OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
            );

            if ($decrypted === false || strlen($decrypted) !== 16) {
                return null;
            }

            $packedKey = substr($decrypted, 0, 8);
            $authenticationTag = substr($decrypted, 8, 8);
            $expectedTag = substr(
                hash_hmac('sha256', static::class.'|'.$packedKey, self::routeAuthenticationKey(), true),
                0,
                8
            );

            if (!hash_equals($expectedTag, $authenticationTag)) {
                return null;
            }

            $unpacked = unpack('Jkey', $packedKey);
            $key = $unpacked['key'] ?? null;

            return is_int($key) && $key > 0 ? (string) $key : null;
        }

        // Keep previously issued signed links working during deployments.
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

    private static function routeEncryptionKey(): string
    {
        return hash('sha256', 'route-encryption|'.self::routeSigningKey(), true);
    }

    private static function routeAuthenticationKey(): string
    {
        return hash('sha256', 'route-authentication|'.self::routeSigningKey(), true);
    }
}
