<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class AuthLoginHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_repeated_failed_attempts(): void
    {
        $email = 'security.login@example.com';
        $throttleKey = $this->throttleKey($email);
        RateLimiter::clear($throttleKey);

        Customer::factory()->create([
            'email' => $email,
            'password' => Hash::make('SecurePass123'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->post(route('login.perform'), [
                'email' => $email,
                'password' => 'WrongPass123',
            ]);

            $response->assertSessionHasErrors('email');
        }

        $limitedResponse = $this->post(route('login.perform'), [
            'email' => $email,
            'password' => 'WrongPass123',
        ]);

        $limitedResponse->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many sign-in attempts.',
            (string) session('errors')->first('email')
        );

        RateLimiter::clear($throttleKey);
    }

    public function test_successful_login_clears_throttle_counter(): void
    {
        $email = 'clear.counter@example.com';
        $throttleKey = $this->throttleKey($email);
        RateLimiter::clear($throttleKey);

        Customer::factory()->create([
            'email' => $email,
            'password' => Hash::make('RightPass123'),
        ]);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->post(route('login.perform'), [
                'email' => $email,
                'password' => 'WrongPass123',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.perform'), [
            'email' => $email,
            'password' => 'RightPass123',
        ])->assertRedirect(route('home'));

        $this->post(route('logout'))->assertRedirect(route('home'));

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = $this->post(route('login.perform'), [
                'email' => $email,
                'password' => 'WrongPass123',
            ]);

            $response->assertSessionHasErrors('email');
            $this->assertStringNotContainsString(
                'Too many sign-in attempts.',
                (string) session('errors')->first('email')
            );
        }

        RateLimiter::clear($throttleKey);
    }

    public function test_staff_login_redirects_to_staff_dashboard_even_with_frontend_intended_url(): void
    {
        $username = 'staff.login';

        Staff::factory()->create([
            'username' => $username,
            'password' => Hash::make('StaffPass123'),
        ]);

        $this->withSession([
            'url.intended' => route('home'),
        ])->post(route('login.perform'), [
            'email' => $username,
            'password' => 'StaffPass123',
        ])->assertRedirect(route('staff.dashboard'));
    }

    public function test_admin_login_redirects_to_admin_dashboard_even_with_frontend_intended_url(): void
    {
        $email = 'admin.login@example.com';

        Admin::factory()->create([
            'email' => $email,
            'password' => Hash::make('AdminPass123'),
        ]);

        $this->withSession([
            'url.intended' => route('home'),
        ])->post(route('login.perform'), [
            'email' => $email,
            'password' => 'AdminPass123',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_inactive_customer_and_staff_accounts_cannot_log_in(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'inactive.customer@example.com',
            'password' => Hash::make('CustomerPass123'),
            'is_active' => false,
        ]);
        $staff = Staff::factory()->create([
            'username' => 'inactive.staff',
            'password' => Hash::make('StaffPass123'),
            'is_active' => false,
        ]);

        foreach ([
            [$customer->email, 'CustomerPass123', 'customer'],
            [$staff->username, 'StaffPass123', 'staff'],
        ] as [$email, $password, $guard]) {
            $this->post(route('login.perform'), compact('email', 'password'))
                ->assertSessionHasErrors('email');

            $this->assertGuest($guard);
            $this->assertSame(
                'This account is inactive. Please contact the administrator.',
                session('errors')->first('email')
            );
        }
    }

    public function test_deactivated_authenticated_account_is_logged_out_on_its_next_request(): void
    {
        $customer = Customer::factory()->create(['is_active' => true]);

        $this->actingAs($customer, 'customer');
        $customer->update(['is_active' => false]);

        $this->get(route('bookings.my'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('customer');
    }

    public function test_google_sign_in_starts_account_creation_for_a_new_email(): void
    {
        Mail::fake();

        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->once()->andReturn('google-new-customer-123');
        $googleUser->shouldReceive('getEmail')->once()->andReturn('new.google.customer@example.com');
        $googleUser->shouldReceive('getName')->once()->andReturn('New Google Customer');

        $provider = Mockery::mock();
        $provider->shouldReceive('redirectUrl')
            ->once()
            ->with(route('auth.google.callback'))
            ->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $response = $this
            ->withSession(['google_auth_intent' => 'login'])
            ->get(route('auth.google.callback'));

        $response
            ->assertRedirect(route('register.verify'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('registration_verifications', [
            'email' => 'new.google.customer@example.com',
            'name' => 'New Google Customer',
            'google_id' => 'google-new-customer-123',
        ]);
        $this->assertSame(
            'new.google.customer@example.com',
            session('pending_registration_email')
        );
        $this->assertDatabaseMissing('customers', [
            'email' => 'new.google.customer@example.com',
        ]);
    }

    private function throttleKey(string $email): string
    {
        return 'login:'.strtolower(trim($email)).'|127.0.0.1';
    }
}
