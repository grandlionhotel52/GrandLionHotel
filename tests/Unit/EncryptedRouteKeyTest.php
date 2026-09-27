<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Room;
use Tests\TestCase;

class EncryptedRouteKeyTest extends TestCase
{
    public function test_route_keys_are_encrypted_stable_and_model_scoped(): void
    {
        $routeKey = Booking::encryptRouteKey(12345);

        $this->assertSame($routeKey, Booking::encryptRouteKey(12345));
        $this->assertSame('12345', Booking::decryptRouteKey($routeKey));
        $this->assertNull(Room::decryptRouteKey($routeKey));
        $this->assertStringNotContainsString('12345', $routeKey);
        $this->assertDoesNotMatchRegularExpression('/^\d+$/', $routeKey);
    }

    public function test_tampered_route_keys_are_rejected(): void
    {
        $routeKey = Booking::encryptRouteKey(12345);
        $lastCharacter = substr($routeKey, -1);
        $tampered = substr($routeKey, 0, -1).($lastCharacter === 'A' ? 'B' : 'A');

        $this->assertNull(Booking::decryptRouteKey($tampered));
    }
}
