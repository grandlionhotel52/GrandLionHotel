<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationReadStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_mark_one_or_all_notifications_without_removing_them(): void
    {
        $customer = Customer::factory()->create();
        $booking = Booking::factory()->create(['customer_id' => $customer->id]);
        $firstId = (string) Str::uuid();
        $secondId = (string) Str::uuid();

        foreach ([
            $firstId => 'Your first booking update.',
            $secondId => 'Your second booking update.',
        ] as $id => $message) {
            DB::table('notifications')->insert([
                'id' => $id,
                'type' => 'Tests\\BookingNotification',
                'notifiable_type' => Customer::class,
                'notifiable_id' => $customer->id,
                'data' => json_encode(['booking_id' => $booking->id, 'message' => $message]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($customer, 'customer')
            ->post(route('notifications.read-one', $firstId))
            ->assertRedirect();

        $this->assertNotNull(DB::table('notifications')->where('id', $firstId)->value('read_at'));
        $this->assertNull(DB::table('notifications')->where('id', $secondId)->value('read_at'));

        $homeResponse = $this->get(route('home'))
            ->assertSuccessful()
            ->assertSee('Your first booking update.')
            ->assertSee('Your second booking update.')
            ->assertSee('Read');
        $homeResponse->assertSee(route('bookings.show', [
            'booking' => $booking,
            'return_to' => '/',
        ]), false);

        $this->get(route('bookings.show', ['booking' => $booking, 'return_to' => '/']))
            ->assertSuccessful()
            ->assertSee('href="/"', false)
            ->assertSee('Back');

        $this->post(route('notifications.read'))->assertRedirect();

        $this->assertSame(0, $customer->fresh()->unreadNotifications()->count());
        $this->get(route('home'))
            ->assertSee('Your first booking update.')
            ->assertSee('Your second booking update.');
    }

    public function test_customer_cannot_mark_another_customers_notification(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'Tests\\BookingNotification',
            'notifiable_type' => Customer::class,
            'notifiable_id' => $owner->id,
            'data' => json_encode([]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($other, 'customer')
            ->post(route('notifications.read-one', $notificationId))
            ->assertNotFound();
    }

    public function test_customer_can_delete_one_or_all_of_their_notifications(): void
    {
        $customer = Customer::factory()->create();
        $firstId = (string) Str::uuid();
        $secondId = (string) Str::uuid();

        foreach ([$firstId, $secondId] as $id) {
            DB::table('notifications')->insert([
                'id' => $id,
                'type' => 'Tests\\BookingNotification',
                'notifiable_type' => Customer::class,
                'notifiable_id' => $customer->id,
                'data' => json_encode(['message' => 'Booking update.']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($customer, 'customer')
            ->delete(route('notifications.delete-one', $firstId))
            ->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $firstId]);
        $this->assertDatabaseHas('notifications', ['id' => $secondId]);

        $this->delete(route('notifications.delete-all'))->assertRedirect();
        $this->assertSame(0, $customer->fresh()->notifications()->count());
    }

    public function test_customer_cannot_delete_another_customers_notification(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'Tests\\BookingNotification',
            'notifiable_type' => Customer::class,
            'notifiable_id' => $owner->id,
            'data' => json_encode([]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($other, 'customer')
            ->delete(route('notifications.delete-one', $notificationId))
            ->assertNotFound();
        $this->assertDatabaseHas('notifications', ['id' => $notificationId]);
    }
}
