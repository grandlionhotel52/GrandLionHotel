<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingPaidMail;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminStaffButtonFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-16 10:30:00');
        $this->seedCleaningStatuses();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_pages_and_actions_work(): void
    {
        Mail::fake();

        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create(['admin_id' => $admin->id]);
        $customer = Customer::factory()->create();
        $orphanCustomer = Customer::factory()->create();
        $room = $this->createRoom();
        $pendingBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'pending',
            paymentStatus: 'unpaid'
        );
        $onlineBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'pending_verification',
            paymentMethod: 'instapay',
            paymentExtras: [
                'qr_reference' => 'QR-ADMIN-001',
                'customer_reference' => 'GCASH-ADMIN-001',
            ]
        );

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard'))->assertOk();
        $roomsIndexResponse = $this->get(route('admin.rooms.index'));
        $roomsIndexResponse->assertOk();
        $roomsIndexResponse->assertSee('Room Status');
        $roomsIndexResponse->assertSee(
            'data-room-update-url="'.route('admin.rooms.update', $room).'"',
            false
        );
        $roomsIndexResponse->assertDontSee('Cleaning Status');
        $roomsIndexResponse->assertDontSee('Needs Attention');
        $roomsIndexResponse->assertDontSee('Needs Cleaning');
        $this->get(route('admin.staff.index'))
            ->assertOk()
            ->assertSee('data-submit-lock', false)
            ->assertSee('data-submitting-text="Creating account..."', false)
            ->assertSee('data-staff-update-url="'.route('admin.staff.update', $staff).'"', false);
        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-submit-lock', false)
            ->assertSee('data-submitting-text="Creating account..."', false)
            ->assertSee('data-user-update-url="'.route('admin.users.update', $customer).'"', false);
        $this->get(route('admin.bookings.show', $pendingBooking))->assertOk();

        $this->post(route('admin.staff.store'), [
            'first_name' => 'Desk',
            'last_name' => 'Staff',
            'email' => 'desk.staff@example.com',
            'phone' => '09170001111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.staff.index'));

        $createdStaff = Staff::query()->where('email', 'desk.staff@example.com')->firstOrFail();
        $this->assertSame($admin->id, $createdStaff->admin_id);

        $this->put(route('admin.staff.update', $createdStaff), [
            'first_name' => 'Desk Staff',
            'last_name' => 'Updated',
            'email' => 'desk.staff.updated@example.com',
            'phone' => '09170002222',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.staff.index'));

        $createdStaff->refresh();
        $this->assertSame('Desk Staff Updated', $createdStaff->name);

        $this->patch(route('admin.bookings.assign-staff', $pendingBooking), [
            'staff_id' => $staff->id,
        ])->assertRedirect(route('admin.bookings.show', $pendingBooking));

        $pendingBooking->refresh();
        $this->assertSame($staff->id, $pendingBooking->staff_id);

        $this->patch(route('admin.bookings.update-status', $pendingBooking), [
            'status' => 'confirmed',
        ])->assertRedirect(route('admin.bookings.show', $pendingBooking));

        $pendingBooking->refresh();
        $this->assertSame('confirmed', $pendingBooking->status);
        $this->assertSame($staff->id, $pendingBooking->staff_id);
        Mail::assertQueued(BookingConfirmedMail::class);

        $dirtyStatusId = (int) RoomStatus::query()->where('slug', 'dirty')->value('room_status_id');
        $this->patch(route('admin.rooms.update-room-status', $room), [
            'room_status_id' => $dirtyStatusId,
        ])->assertRedirect();

        $room->refresh();
        $this->assertSame($dirtyStatusId, $room->room_status_id);
        $this->assertSame($admin->id, $room->admin_id);
        $this->assertNotNull($room->status_updated_at);

        $this->patch(route('admin.bookings.approve-online-payment', $onlineBooking), [
            'verified_amount' => 'not-a-number',
        ])->assertSessionHasErrors('verified_amount');

        $this->patch(route('admin.bookings.approve-online-payment', $onlineBooking), [
            'verified_amount' => $onlineBooking->payment->amount,
        ])
            ->assertRedirect(route('admin.bookings.show', $onlineBooking));

        $onlineBooking->refresh();
        $onlineBooking->load('payment');
        $this->assertSame('paid', $onlineBooking->payment->status);
        $this->assertNotNull($onlineBooking->payment->transaction_reference);
        Mail::assertQueued(BookingPaidMail::class);

        $this->put(route('admin.users.update', $orphanCustomer), [
            'first_name' => 'Updated',
            'last_name' => 'Customer',
            'email' => 'updated.customer@example.com',
            'phone' => '09179999999',
            'address_line' => 'Updated Street',
            'city' => 'Calamba',
            'province' => 'Laguna',
            'country' => 'Philippines',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $orphanCustomer->refresh();
        $this->assertSame('Updated Customer', $orphanCustomer->name);

        $this->delete(route('admin.users.destroy', $orphanCustomer))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('customers', ['customer_id' => $orphanCustomer->id]);

        $this->delete(route('admin.staff.destroy', $createdStaff))
            ->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseMissing('staff', ['staff_id' => $createdStaff->id]);
    }

    public function test_admin_can_create_customer_and_duplicate_emails_are_validation_errors(): void
    {
        $admin = Admin::factory()->create(['email' => 'admin@example.com']);
        $staff = Staff::factory()->create(['email' => 'staff@example.com']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.users.store'), [
                '_user_modal_mode' => 'create',
                'first_name' => 'New',
                'last_name' => 'Customer',
                'email' => '  CUSTOMER@EXAMPLE.COM ',
                'phone' => '09171234567',
                'address_line' => 'Sample Street',
                'city' => 'Calamba',
                'province' => 'Laguna',
                'country' => 'Philippines',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $customer = Customer::query()->where('email', 'customer@example.com')->firstOrFail();
        $this->assertSame('New Customer', $customer->name);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.users.index'))
            ->post(route('admin.users.store'), [
                '_user_modal_mode' => 'create',
                'first_name' => 'Duplicate',
                'last_name' => 'Customer',
                'email' => 'Customer@Example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin, 'admin')
            ->from(route('admin.staff.index'))
            ->post(route('admin.staff.store'), [
                '_staff_modal_mode' => 'create',
                'first_name' => 'Duplicate',
                'last_name' => 'Staff',
                'email' => ' CUSTOMER@EXAMPLE.COM ',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.staff.index'))
            ->assertSessionHasErrors('email');

        $this->actingAs($admin, 'admin')
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $customer), [
                '_user_modal_mode' => 'edit',
                '_user_modal_id' => $customer->id,
                'first_name' => 'New',
                'last_name' => 'Customer',
                'email' => strtoupper($customer->email),
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.users.index'))
            ->put(route('admin.users.update', $customer), [
                '_user_modal_mode' => 'edit',
                '_user_modal_id' => $customer->id,
                'first_name' => 'New',
                'last_name' => 'Customer',
                'email' => strtoupper($staff->email),
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('email');
    }

    public function test_staff_can_filter_arrivals_board_by_date(): void
    {
        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom();
        $selectedDate = Carbon::today()->addDays(3)->toDateString();

        $arrival = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'unpaid'
        );
        $arrival->update([
            'check_in' => $selectedDate,
            'check_out' => Carbon::parse($selectedDate)->addDay()->toDateString(),
        ]);

        $this->actingAs($staff, 'staff')
            ->get(route('staff.arrivals', ['date' => $selectedDate]))
            ->assertOk()
            ->assertSee('Showing arrivals for '.Carbon::parse($selectedDate)->format('M d, Y'))
            ->assertSee('#'.$arrival->id)
            ->assertSee('value="'.$selectedDate.'"', false)
            ->assertSee('id="arrival_date_form"', false)
            ->assertSee("arrivalDateInput.form?.requestSubmit()", false)
            ->assertDontSee('Show arrivals');
    }

    public function test_staff_pages_and_operational_actions_work(): void
    {
        Mail::fake();

        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom();
        $walkInRoom = $this->createRoom(['name' => 'Walk-in Room 1']);

        $pendingBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'pending',
            paymentStatus: 'unpaid'
        );
        $activeBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'unpaid'
        );
        $activeBooking->update(['staff_id' => $staff->id]);
        $onlineBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'pending_verification',
            paymentMethod: 'instapay',
            paymentExtras: [
                'qr_reference' => 'QR-STAFF-001',
                'customer_reference' => 'GCASH-STAFF-001',
            ]
        );
        $rejectedBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'pending_verification',
            paymentMethod: 'credit_debit_card',
            paymentExtras: [
                'qr_reference' => 'QR-STAFF-REJECT',
                'customer_reference' => 'PAYMAYA-STAFF-001',
            ]
        );

        $this->actingAs($staff, 'staff');

        $dashboardResponse = $this->get(route('staff.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee(route('staff.bookings.create'), false);
        $dashboardResponse->assertDontSee('/staff/rooms', false);
        $this->get(route('staff.arrivals'))->assertOk();
        $bookingsIndexResponse = $this->get(route('staff.bookings.index'));
        $bookingsIndexResponse->assertOk();
        $bookingsIndexResponse->assertSee(route('staff.bookings.create'), false);
        $bookingsIndexResponse->assertSee('id="staff_booking_filter_form"', false);
        $bookingsIndexResponse->assertSee("filterForm?.requestSubmit()", false);
        $bookingsIndexResponse->assertSee('id="staff_booking_results"', false);
        $bookingsIndexResponse->assertSee('await fetch(url', false);
        $bookingsIndexResponse->assertDontSee('Apply filters');
        $this->get(route('staff.bookings.show', [
            'booking' => $activeBooking,
            'return_to' => '/staff/bookings?queue=arrivals_today',
        ]))
            ->assertOk()
            ->assertSee('href="/staff/bookings?queue=arrivals_today"', false);
        $this->get(route('staff.bookings.create'))->assertOk();
        $this->get('/staff/rooms')->assertNotFound();

        $this->patch(route('staff.bookings.confirm', $pendingBooking))
            ->assertRedirect(route('staff.bookings.show', $pendingBooking));

        $pendingBooking->refresh();
        $this->assertSame('confirmed', $pendingBooking->status);
        $this->assertNull($pendingBooking->staff_id);
        Mail::assertQueued(BookingConfirmedMail::class);

        $this->patch(route('staff.bookings.staff-notes', $activeBooking), [
            'staff_notes' => 'Front desk verified the guest documents.',
        ])->assertRedirect(route('staff.bookings.show', $activeBooking));

        $activeBooking->refresh();
        $this->assertSame('Front desk verified the guest documents.', $activeBooking->staff_notes);

        $this->patch(route('staff.bookings.record-payment', $activeBooking), [
            'method' => 'cash',
            'discount_type' => 'none',
        ])->assertRedirect(route('staff.bookings.show', $activeBooking));

        $activeBooking->refresh();
        $activeBooking->load('payment');
        $this->assertSame('paid', $activeBooking->payment->status);
        $this->assertSame('cash', $activeBooking->payment->method);
        $this->assertSame($staff->id, $activeBooking->staff_id);
        Mail::assertQueued(BookingPaidMail::class);

        $this->patch(route('staff.bookings.check-in', $activeBooking), [
            'return_to' => '/staff/bookings?queue=arrivals_today',
        ])->assertRedirect('/staff/bookings?queue=arrivals_today');

        $activeBooking->refresh();
        $this->assertNotNull($activeBooking->actual_check_in_at);

        Carbon::setTestNow(Carbon::now()->addHour());

        $this->patch(route('staff.bookings.check-out', $activeBooking), [])
            ->assertRedirect(route('staff.bookings.show', $activeBooking));

        $activeBooking->refresh();
        $this->assertSame('completed', $activeBooking->status);
        $this->assertNotNull($activeBooking->actual_check_out_at);

        $walkInResponse = $this->post(route('staff.bookings.store'), [
            'customer_name' => 'Walk In Guest',
            'customer_email' => 'walkin@example.com',
            'customer_phone' => '09178888888',
            'room_id' => $walkInRoom->id,
            'check_in' => Carbon::now()->toDateString(),
            'check_out' => Carbon::now()->copy()->addDay()->toDateString(),
            'guests' => 2,
            'extra_bedding_confirmed' => '1',
            'payment_preference' => 'cash',
            'notes' => 'Walk-in from front desk.',
        ]);
        $walkInResponse->assertSessionHasNoErrors();
        $walkInResponse->assertRedirect();

        $walkInBooking = Booking::query()
            ->where('room_id', $walkInRoom->id)
            ->whereNull('customer_id')
            ->latest('booking_id')
            ->first();

        $this->assertNotNull($walkInBooking);
        $this->assertSame($staff->id, $walkInBooking->staff_id);
    }

    private function seedCleaningStatuses(): void
    {
        RoomStatus::query()->firstOrCreate(
            ['slug' => 'clean'],
            ['name' => 'Clean', 'description' => 'Ready for booking']
        );
        RoomStatus::query()->firstOrCreate(
            ['slug' => 'dirty'],
            ['name' => 'Dirty', 'description' => 'Not ready for booking']
        );
    }

    private function createRoom(array $attributes = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'room_status_id' => $cleanStatusId,
        ], $attributes));
    }

    private function createBooking(
        Customer $customer,
        Room $room,
        string $status,
        string $paymentStatus,
        string $paymentMethod = 'pending',
        array $paymentExtras = []
    ): Booking {
        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => Carbon::now()->toDateString(),
            'check_out' => Carbon::now()->copy()->addDay()->toDateString(),
            'status' => $status,
            'notes' => 'Automated test booking',
        ]);

        $booking->guestDetail()->create([
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'adults' => 2,
            'kids' => 0,
            'payment_preference' => $paymentMethod === 'pending' ? 'cash' : $paymentMethod,
        ]);

        $paymentPayload = array_merge([
            'amount' => $room->price_per_night,
            'method' => $paymentMethod,
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? now() : null,
        ], $paymentExtras);

        $booking->payment()->create($paymentPayload);

        return $booking->fresh(['payment', 'guestDetail', 'room', 'user']);
    }
}
